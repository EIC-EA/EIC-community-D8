<?php

namespace Drupal\eic_search\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Collector\SourcesCollector;
use Drupal\eic_search\Plugin\search_api\processor\GroupAccessContent;
use Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface;
use Drupal\eic_search\Search\Sources\NewsStorySourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\eic_search\Search\Sources\UserGallerySourceType;
use Drupal\eic_search\Search\Sources\UserInvitesListSourceType;
use Drupal\eic_search\Search\Sources\UserListSourceType;
use Drupal\eic_search\Search\Sources\UserRecommendSourceType;
use Drupal\eic_search\Search\Sources\UserTaggingCommentsSourceType;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\Group;
use Drupal\group\GroupMembership;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_solr\SolrConnectorInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Solarium\Component\ComponentAwareQueryInterface;
use Solarium\QueryType\Select\Query\Query;

class SolrSearchManager {

  /**
   * @var AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\eic_search\Collector\SourcesCollector
   */
  private SourcesCollector $sourcesCollector;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private EntityTypeManager $em;

  /**
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  private GroupMembershipLoaderInterface $membershipLoader;

  /**
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  private GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage;

  /**
   * @var \Drupal\eic_search\Search\Sources\SourceTypeInterface
   */
  private SourceTypeInterface $source;

  /**
   * @var array
   */
  private array $facets;

  /**
   * @var array
   */
  private array $interests;

  /**
   * @var \Solarium\QueryType\Select\Query\Query
   */
  private Query $solrQuery;

  /**
   * @var Index
   */
  private Index $index;

  /**
   * @var string|null
   */
  private ?string $currentGroup;

  /**
   * @var int|null
   */
  private ?int $userIdFromRoute;

  /**
   * @var \Drupal\search_api_solr\SolrConnectorInterface
   */
  private SolrConnectorInterface $connector;

  /**
   * 'fq' value in solr.
   *
   * @var string
   */
  private string $rawFieldQuery;

  /**
   * 'q' value in solr.
   *
   * @var string
   */
  private string $rawQuery;

  /**
   * @param AccountProxyInterface $current_user
   * @param \Drupal\eic_search\Collector\SourcesCollector $sources_collector
   * @param \Drupal\Core\Entity\EntityTypeManager $em
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $group_visibility_storage
   */
  public function __construct(
    AccountProxyInterface $current_user,
    SourcesCollector $sources_collector,
    EntityTypeManager $em,
    GroupMembershipLoaderInterface $membership_loader,
    GroupVisibilityDatabaseStorageInterface $group_visibility_storage
  ) {
    $this->currentUser = $current_user;
    $this->sourcesCollector = $sources_collector;
    $this->em = $em;
    $this->membershipLoader = $membership_loader;
    $this->groupVisibilityStorage = $group_visibility_storage;
  }

  /**
   * @param string $source_class
   * @param array|null $facets_fields
   *
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  public function init(string $source_class): self {
    $index_storage = $this->em
      ->getStorage('search_api_index');
    $this->index = $index_storage->load('global');
    $this->currentGroup = NULL;

    /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $backend */
    $backend = $this->index->getServerInstance()->getBackend();
    $config = $backend->getConfiguration();
    $backend->setConfiguration($config);
    /** @var \Drupal\search_api_solr\Plugin\SolrConnector\BasicAuthSolrConnector $connector */
    $this->connector = $backend->getSolrConnector();
    $this->solrQuery = $this->connector->getSelectQuery();
    $this->rawQuery = '';

    $sources = $this->sourcesCollector->getSources();
    $this->source = array_key_exists($source_class, $sources) ? $sources[$source_class] : NULL;
    $datasources = $this->source->getSourcesId();
    $datasources_query = [];

    foreach ($datasources as $datasource) {
      $datasources_query[] = 'ss_search_api_datasource:"entity:' . $datasource . '"';
    }

    $this->rawFieldQuery = '(' . implode(' OR ', $datasources_query) . ')';

    return $this;
  }

  /**
   * Set user id from url if needed to build query.
   *
   * @param int|null $user_id
   */
  public function buildUserIdFromUrl(?int $user_id) {
    $this->userIdFromRoute = $user_id;
  }

  /**
   * Build the search query.
   *
   * @param string|NULL $search_value
   */
  public function buildSearchQuery(?string $search_value) {
    $spell_check = $this->solrQuery->getSpellcheck();
    $spell_check->setQuery($search_value);

    $search_fields_id = $this->source ? $this->source->getSearchFieldsId() : NULL;

    if (!$search_fields_id) {
      return;
    }

    $search_query_value = $search_value ? "\"$search_value\"" : '*';

    $query_fields = [];

    foreach ($search_fields_id as $search_field_id) {
      $query_fields[] = "$search_field_id:$search_query_value";
    }

    $this->rawQuery .= empty($this->rawQuery) ?
      '(' . implode(' OR ', $query_fields) . ')' :
      ' AND (' . implode(' OR ', $query_fields) . ')';

    $source_type = get_class($this->source);
    switch ($source_type) {
      case UserGallerySourceType::class:
      case UserInvitesListSourceType::class:
      case UserListSourceType::class:
      case UserRecommendSourceType::class:
      case UserTaggingCommentsSourceType::class:
        // Filter out blocked users.
        $this->rawQuery .= empty($this->rawQuery) ? '(bs_status : true)' : ' AND (bs_status : true)';
        break;
    }
  }

  /**
   * Build the sort and facets to the query.
   *
   * @param array|null $facets_value
   * @param string|null $sort_value
   */
  public function buildSortFacets(?array $facets_value, ?string $sort_value) {
    $this->facets = $facets_value;
    $this->interests = [];
    $sorts = [];

    if (array_key_exists('interests', $this->facets)) {
      $this->interests = $this->facets['interests'];
    }

    if ($sort_value) {
      $sorts = explode('__', $sort_value);

      //Normally sort key have this structure 'FIELD__ASC' but add double check
      if (2 === count($sorts)) {
        // Check if sort needs a group injection before sending to solr.
        $sort_fields_group_context = [
          DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID_GROUP,
          DocumentProcessorInterface::SOLR_GROUP_ROLES,
        ];

        if (in_array($sorts[0], $sort_fields_group_context)) {
          $sorts[0] = $sorts[0] . $this->currentGroup;
        }
        $this->solrQuery->addSort($sorts[0], $sorts[1]);
      }
    }

    if (
      NULL !== $sorts &&
      array_key_exists(0, $sorts) &&
      array_key_exists(SourceTypeInterface::SECOND_SORT_KEY, $this->source->getAvailableSortOptions()[$sorts[0]])
    ) {
      $second_sorts = $this->source->getAvailableSortOptions()[$sorts[0]][SourceTypeInterface::SECOND_SORT_KEY];

      foreach ($second_sorts as $second_sort) {
        $this->solrQuery->addSort($second_sort['id'], $second_sort['direction']);
      }
    }

    $default_sort = $this->source->getSecondDefaultSort();

    // Add second sort.
    if (!empty($default_sort) && 2 === count($default_sort)) {
      $this->solrQuery->addSort($default_sort[0], $default_sort[1]);
    }

    $default_sort = $this->source->getDefaultSort();

    if ($this->currentGroup && DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID === $default_sort[0]) {
      $default_sort[0] = DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID_GROUP . $this->currentGroup;
    }

    // If there are no current sorts check if source has a default sort.
    if (
      !$sort_value &&
      $this->source instanceof SourceTypeInterface &&
      $default_sort = $this->source->getDefaultSort()
    ) {
      $this->solrQuery->addSort($default_sort[0], $default_sort[1]);
    }
    $facets_query = '';

    // Cleaning non solr fields from facets before putting it to the query.
    $this->cleanFacets();
    foreach ($this->facets as $key => $facet_value) {
      $filtered_value = array_filter($facet_value, function ($value) {
        return $value;
      });

      $values = array_keys($filtered_value);

      if ($filtered_value) {
        array_walk($values, function (&$value) {
          $value = "\"$value\"";
        });

        $query_values = count($values) > 1 ?
          '(' . implode(' AND ', $values) . ')' :
          implode(' AND ', $values);

        if (in_array($key, DocumentProcessorInterface::SOLR_FIELD_NEED_GROUP_INJECT)) {
          $key = $key . $this->currentGroup;
        }

        $facets_query .= ' AND ' . $key . ':' . $query_values;
      }
    }

    if ($facets_query) {
      $this->rawFieldQuery .= $facets_query;
    }
  }

  /**
   * Build date query.
   *
   * @param string $from_date
   * @param string $end_date
   */
  public function buildDateQuery(string $from_date = '*', string $end_date = '*') {
    $filter_registration = FALSE;

    if (array_key_exists('filter_registration', $this->facets)) {
      $filter_registration = $this->facets['filter_registration']['open_registration'];
    }

    // If source supports date filter and query has a from or to date.
    if ($this->source->supportDateFilter() && ($from_date || $end_date)) {
      $date_fields_id = $this->source->getDateIntervalField();
      $date_from_id = $date_fields_id['from'];
      $date_end_id = $date_fields_id['to'];

      // If user only selected one day, we will only filter on the start date.
      // for eg: user select on widget 23-11-2021 - 23-11-2021 (double click)
      // we only do a query from 23-11-2021 to *.
      $end_date = $end_date === $from_date ? (int) $end_date + 86400 : $end_date;
      $dates_query = [];

      $dates_query[] = "($date_from_id:[$from_date TO $end_date] AND $date_end_id:[$from_date TO $end_date])";
      $dates_query[] = "($date_from_id:[* TO $end_date] AND $date_end_id:[$from_date TO $end_date])";
      $dates_query[] = "($date_from_id:[$from_date TO $end_date] AND $date_end_id:[$end_date TO *])";
      $dates_query[] = "($date_from_id:[* TO $from_date] AND $date_end_id:[$end_date TO *])";
      $date_query = implode(' OR ', $dates_query);

      $date_query = "($date_query)";
      $this->rawQuery .= empty($this->rawQuery) ?
        "$date_query" :
        " AND $date_query";
    }

    if ($this->source->getRegistrationDateIntervalField() && $filter_registration) {
      $date_fields_id = $this->source->getRegistrationDateIntervalField();
      $date_from_id = $date_fields_id['from'];
      $date_end_id = $date_fields_id['to'];
      $now = time();
      $dates_query = [];

      $dates_query[] = "($date_from_id:[* TO $now] AND $date_end_id:[$now TO *])";
      $date_query = implode(' OR ', $dates_query);

      $date_query = "($date_query)";
      $this->rawQuery .= empty($this->rawQuery) ?
        "$date_query" :
        " AND $date_query";
    }
  }

  /**
   * Set all facets to our SOLR request.
   *
   * @param array|null $facets_fields
   */
  public function buildFacets(?array $facets_fields) {
    $facets_fields = $facets_fields ?? [];
    $facets_fields = array_map(function ($facet) {
      if (!in_array($facet, DocumentProcessorInterface::SOLR_FIELD_NEED_GROUP_INJECT)) {
        return $facet;
      }

      return $facet . $this->currentGroup;
    }, $facets_fields);

    $this->solrQuery->addParam('facet.field', $facets_fields);
  }

  /**
   * Build group query.
   *
   * @param string|NULL $current_group
   */
  public function buildGroupQuery(?string $current_group) {
    if (!$current_group) {
      return;
    }

    $this->currentGroup = $current_group;

    if (
      !$this->source->excludingCurrentGroup() &&
      $group_id_fields = $this->source->getPrefilteredGroupFieldId()
    ) {
      $group_query = [];
      foreach ($group_id_fields as $group_id_field) {
        $group_query[] = "$group_id_field:($current_group)";
      }
      $group_query_string = '(' . implode(' OR ', $group_query) . ')';
      $this->rawQuery .= empty($this->rawQuery) ?
        "$group_query_string" :
        " AND $group_query_string";
    }

    if (
      $this->source instanceof SourceTypeInterface &&
      $this->source->prefilterByGroupVisibility()
    ) {
      $this->generateUsersQueryVisibilityGroup(
        $current_group,
        $this->source instanceof UserRecommendSourceType
      );
    }

    if (
      $this->source instanceof SourceTypeInterface &&
      $this->source->excludingCurrentGroup()
    ) {
      $this->generateExcludingUsersGroupQuery($current_group);
    }
  }

  /**
   * Prefilter the query with the topic.
   *
   * @param int|null $topic_term_id
   */
  public function buildPrefilterTopic(?int $topic_term_id) {
    if (!$topic_term_id) {
      return;
    }

    // Handle current term ID sub-query.
    $term_id_fields = $this->source->getPrefilteredTopicsFieldId();
    $term_query = [];
    foreach ($term_id_fields as $term_id_field) {
      $term_query[] = "$term_id_field:($topic_term_id)";
    }
    $term_query_string = '(' . implode(' OR ', $term_query) . ')';
    $this->rawQuery .= empty($this->rawQuery) ?
      "$term_query_string" :
      " AND $term_query_string";
  }

  /**
   * Set the current start and offset.
   *
   * @param int $page
   * @param int $offset
   */
  public function buildQueryPager(int $page, int $offset) {
    $this->solrQuery->setRows($offset);

    //Default value will be to work like pagination.
    if ($this->source instanceof SourceTypeInterface && $this->source->allowPagination()) {
      $this->solrQuery->setStart(($page * $offset) - $offset);
      return;
    }

    //If no pagination, it's a load more so we start at 1.
    $this->solrQuery->setStart(0);
    $this->solrQuery->setRows($offset * $page);
  }

  /**
   * @return string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   */
  public function search() {
    $this->solrQuery->addParam('json.nl', 'arrarr');
    $this->solrQuery->addParam('facet.mincount', 1);
    $this->solrQuery->addParam('facet', 'on');
    $this->solrQuery->addParam('facet.sort', 'false');
    $this->solrQuery->addParam('wt', 'json');

    $this->generatePrefilterEmptyValues();
    $this->generateSpellCheck();
    $this->generateExcludingUser();
    $this->generateQueryInterests();
    $this->generateQueryUserGroupsAndContents();
    $this->generateQueryPrivateContent();
    $this->generateQueryPublishedState();
    $this->generatePrefilterGroupsMembership();
    $this->generatePrefilterByCurrentUser();
    $this->generatePrefilterContentTypes();
    $this->generateExtraPrefilter();
    $this->generateAvoidGroupBookPage();
    $this->generateIgnoreAnonymousNewsStoriesContent();

    $this->solrQuery->addParam('q', !empty($this->rawQuery) ? $this->rawQuery : '*:*');
    $this->solrQuery->addParam('fq', $this->rawFieldQuery);

    $is_admin_group = FALSE;

    if ($this->currentGroup && $current_group_entity = Group::load($this->currentGroup)) {
      $group_owner = EICGroupsHelper::getGroupOwner($current_group_entity);
      $group_admins = EICGroupsHelper::getGroupAdmins($current_group_entity);

      $filtered_admins = array_filter($group_admins, function (GroupMembership $member) {
        return $member->getUser()->id() === $this->currentUser->id();
      });

      $is_admin_group =
        ($group_owner instanceof UserInterface && $group_owner->id() === $this->currentUser->id()) ||
        !empty($filtered_admins);
    }

    if (
      !$is_admin_group &&
      $this->index->isValidProcessor('group_content_access') &&
      GroupAccessContent::supportsIndex($this->index)
    ) {
      $this->index->getProcessor('group_content_access')
        ->preprocessSolrSearchQuery($this->solrQuery);
    }

    return $this->connector->search($this->solrQuery)->getBody();
  }

  /**
   * Generate query for user interests matching by their topics.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function generateQueryInterests() {
    if (
      empty($this->interests) ||
      !array_key_exists('my_interests', $this->interests) ||
      !$this->interests['my_interests']
    ) {
      return;
    }

    $user_id = $this->currentUser->id();
    $profiles = $this->em
      ->getStorage('profile')
      ->loadByProperties([
        'uid' => $user_id,
        'type' => 'member',
      ]);

    if (empty($profiles)) {
      return;
    }

    $profile = reset($profiles);
    $user_topics = $profile->get(Topics::TERM_TOPICS_ID_FIELD)
      ->referencedEntities();
    $user_topics_id = [0];

    if ($user_topics) {
      $user_topics_id = array_map(function (Term $topic) {
        return $topic->id();
      }, $user_topics);
    }

    $user_topics_string = implode(' OR ', $user_topics_id);
    $this->rawQuery .= " AND (itm_group_field_vocab_topics:($user_topics_string) OR itm_content_field_vocab_topics:($user_topics_string) OR itm_message_node_ref_field_vocab_topics:($user_topics_string))";
  }

  /**
   * Generate query for user's groups and content.
   */
  private function generateQueryUserGroupsAndContents() {
    if (
      empty($this->interests) ||
      !array_key_exists('my_groups', $this->interests) ||
      !$this->interests['my_groups']
    ) {
      return;
    }

    $user_id = $this->currentUser->id();
    $user = User::load($user_id);

    $groups_membership = $this->membershipLoader->loadByUser($user);

    if ($groups_membership) {
      $groups_membership_id = array_map(function (GroupMembership $group_membership) {
        return $group_membership->getGroup()->id();
      }, $groups_membership);
    }

    $groups_membership_string = $groups_membership_id ? implode(' OR ', $groups_membership_id) : -1;
    $group_field_id = $this->source->getPrefilteredGroupFieldId();
    $group_field_id = reset($group_field_id);

    $this->rawFieldQuery .= " AND ($group_field_id:($groups_membership_string) OR its_global_group_parent_id:($groups_membership_string) OR its_content_uid:($groups_membership_string))";
  }

  /**
   * Generate query for private content.
   */
  private function generateQueryPrivateContent() {
    $roles = $this->currentUser->getRoles();

    if (
      in_array(UserHelper::ROLE_TRUSTED_USER, $roles) ||
      UserHelper::isPowerUser($this->currentUser)
    ) {
      return;
    }

    $this->rawFieldQuery .= " AND bs_content_is_private:false";
  }

  /**
   * Add the status query to the query but check for groups if need
   * to show draft/pending for group owner.
   */
  private function generateQueryPublishedState() {
    if (!$this->source instanceof SourceTypeInterface || $this->source->ignorePublishedState()) {
      return;
    }

    $user_id = $this->currentUser->id();
    $is_power_user = UserHelper::isPowerUser($this->currentUser);

    // We need to ignore the publish state for power users.
    if ($is_power_user) {
      return;
    }

    $status_query = ' AND (bs_global_status:true';

    // User can see their group if status false but he is owner.
    if ('group' === $this->source->getEntityBundle()) {
      $status_query .= ' OR (its_group_owner_id:' . $user_id . ')';
    }

    $query_bundle = [
      "its_group_owner_id:$user_id",
    ];

    switch ($this->source->getEntityBundle()) {
      case 'library':
      case 'discussion':
      case 'node_event':
      case 'news':
        // Show own content even if it's in draft, archived, ...
        $query_bundle[] = "its_content_uid:$user_id";
        break;

      case 'group':
        $query_bundle[] = 'its_global_group_parent_published:1';
        break;

      case 'activity_stream':
        $query_bundle[] = "its_uid:$user_id";
        break;

      case 'global':
        $query_bundle[] = "its_content_uid:$user_id";
        break;
    }

    $status_query .= ' OR (' . implode(' OR ', $query_bundle) . ')';

    $status_query .= ')';

    $this->rawFieldQuery .= $status_query;
  }

  /**
   * Prefilter users by the current group visibility.
   *
   * @param string $group_id
   *   The group id.
   * @param bool $strict_private
   *   If TRUE, it will filter only member of private group and NOT people that are allowed to be invited.
   */
  private function generateUsersQueryVisibilityGroup($group_id, bool $strict_private = FALSE) {
    $query = '';
    $group = Group::load($group_id);

    $group_visibility_entity = $this->groupVisibilityStorage->load($group->id());
    $visibility_type = $group_visibility_entity ?
      $group_visibility_entity->getType() :
      NULL;

    switch ($visibility_type) {
      case GroupVisibilityType::GROUP_VISIBILITY_PRIVATE:
      case GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY:
        if ($strict_private) {
          $query = '(itm_user_group_ids:(' . $group_id . '))';
        }
        else {
          $query = '(sm_user_profile_role_array:*' . UserHelper::ROLE_TRUSTED_USER . '*)';
        }
        break;

      // In this case, when we have a custom restriction, we can have multiple restriction options like email domain, trusted users, organisation, ...
      case GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED:
        $options = $group_visibility_entity->getOptions();
        foreach ($options as $key => $option) {
          // restricted_email_domains_status can be false so we need to check if enable
          if (GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN === $key && $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . '_status']) {
            $authorized_emails = $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . '_conf'];
            $authorized_emails = str_replace(' ', '', $authorized_emails);
            $emails = explode(',', $authorized_emails);
            $emails = implode(' OR *', $emails);
            $query = '(ss_user_mail:(*' . $emails . '))';
          }

          if (GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS === $key && $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . '_status']) {
            $user_ids = $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . '_conf'];
            $users = array_map(function ($user_id) {
              return $user_id['target_id'];
            }, $user_ids);
            $users = implode(' OR ', $users);
            $query = '(its_user_id:(' . $users . '))';
          }
        }
        break;
      default:
        break;
    }

    if (empty($query)) {
      return;
    }

    $this->rawFieldQuery .= !empty($this->rawFieldQuery) ? " AND $query" : "$query";
  }

  /**
   * Prefilter users by excluding the current group.
   *
   * @param $group_id
   */
  private function generateExcludingUsersGroupQuery($group_id) {
    if (!empty($this->rawFieldQuery)) {
      $this->rawFieldQuery .= " AND !(itm_user__group_content__uid_gid:($group_id))";
      return;
    }

    $this->rawFieldQuery .= "!itm_user__group_content__uid_gid:($group_id)";
  }

  /**
   * Generate spellcheck for solr.
   */
  private function generateSpellcheck() {
    $spell_check = $this->solrQuery->getSpellcheck();
    $spell_check->setDictionary('en');
    $spell_check->setAccuracy(0.5);
    $spell_check->setCollate(TRUE);
    $spell_check->setReload(TRUE);
    $this->solrQuery->setComponent(ComponentAwareQueryInterface::COMPONENT_SPELLCHECK, $spell_check);
  }

  /**
   * Exclude anonymous user and/or current user if configured.
   */
  private function generateExcludingUser() {
    if (
      $this->source instanceof SourceTypeInterface &&
      $this->source->ignoreAnonymousUser()
    ) {
      $this->rawFieldQuery .= ' AND !(' . $this->source->getAuthorFieldId() . ':0)';
    }

    if ($this->source->ignoreContentFromCurrentUser()) {
      $this->rawFieldQuery .= ' AND !(' . $this->source->getAuthorFieldId() . ':' . $this->currentUser->id() . ')';
    }
  }

  /**
   * Get only items from current user.
   */
  private function generatePrefilterByCurrentUser() {
    if (!$this->source->prefilterByCurrentUser()) {
      return;
    }

    $this->rawFieldQuery .= ' AND (' . $this->source->getAuthorFieldId() . ':(' . $this->currentUser->id() . '))';
  }

  /**
   * Filter items by groups where user is member of.
   */
  private function generatePrefilterGroupsMembership() {
    if (!$this->source->prefilterByGroupsMembership()) {
      return;
    }

    $current_user = $this->source->prefilterByUserFromRoute() ?
      User::load($this->userIdFromRoute) :
      $this->currentUser;

    if (!$current_user) {
      return;
    }

    $grps = $this->membershipLoader->loadByUser($current_user);

    $grp_ids = array_map(function (GroupMembership $grp_membership) {
      return $grp_membership->getGroup()->id();
    }, $grps);

    $group_filters_id = $this->source->getPrefilteredGroupFieldId();

    if (!$group_filters_id) {
      return;
    }

    if (empty($grp_ids)) {
      $this->rawFieldQuery .= ' AND (' . reset($group_filters_id) . ':("-1"))';
      return;
    }

    $this->rawFieldQuery .= ' AND (' . reset($group_filters_id) . ':(' . implode(' OR ', $grp_ids) . ' OR "-1"))';
  }

  /**
   * Prefilter values only by certain content types.
   */
  private function generatePrefilterContentTypes() {
    $content_types = $this->source->getPrefilteredContentType();

    if (!$content_types) {
      return;
    }

    $allowed_content_type = implode(' OR ', $content_types);
    $this->rawFieldQuery .= ' AND (' . SourceTypeInterface::SOLR_FIELD_CONTENT_TYPE_ID . ':(' . $allowed_content_type . '))';
  }

  /**
   * Prefilter extra prefilter from source.
   */
  private function generateExtraPrefilter() {
    $extra_filters = $this->source->extraPrefilter();
    $query_extra_filter = [];

    // Add filters by AND operator.
    if (!empty($extra_filters['AND'])) {
      foreach ($extra_filters['AND'] as $field => $values) {
        $query_extra_filter[] = "$field:(" . implode(' AND ', $values) . ")";
      }
    }

    // Add filters by OR operator.
    if (!empty($extra_filters['OR'])) {
      foreach ($extra_filters['OR'] as $field => $values) {
        $query_extra_filter[] = "$field:(" . implode(' OR ', $values) . ")";
      }
    }

    if (empty($query_extra_filter)) {
      return;
    }

    $query_extra_filter = implode(' AND ', $query_extra_filter);
    $this->rawFieldQuery .= " AND ($query_extra_filter)";
  }

  /**
   * Prefilter values where there is no data in it.
   */
  private function generatePrefilterEmptyValues() {
    $fields_filter_empty = $this->source->getFieldsToFilterEmptyValue();

    if (empty($fields_filter_empty)) {
      return;
    }

    foreach ($fields_filter_empty as $field) {
      $this->rawQuery .= empty($this->rawQuery) ?
        "$field:[* TO *]" :
        " AND $field:[* TO *]";
    }
  }

  /**
   * Prefilter book page pre-generated by group creation.
   */
  private function generateAvoidGroupBookPage() {
    // Add filter if it's a book page not in a group. To avoid pre-generated book page.
    // Solr cannot handle negate OR in parentheses, so we need to do the reverse condition by negate it.
    $query = '-(!its_global_group_parent_id:("-1") OR (ss_global_content_type:book))';

    $this->rawFieldQuery .= empty($this->rawFieldQuery) ?
      "$query" :
      " AND $query";
  }

  /**
   * Prefilter news/story from content where user is anonymous and content coming from Organisation.
   */
  private function generateIgnoreAnonymousNewsStoriesContent() {
    if (!$this->source instanceof NewsStorySourceType || !$this->currentUser->isAnonymous()) {
      return;
    }

    // Do not include organisation news as anonymous.
    $query = '(ss_global_group_parent_type:("" OR event OR group))';

    $this->rawFieldQuery .= empty($this->rawFieldQuery) ?
      "$query" :
      " AND $query";
  }

  /**
   * Clean useless variables for solr before sending it.
   */
  private function cleanFacets() {
    unset($this->facets['interests']);
    unset($this->facets['filter_registration']);
  }

}
