<?php

namespace Drupal\eic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_search\Plugin\search_api\processor\GroupAccessContent;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\Group;
use Drupal\group\GroupMembership;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Solarium\Component\ComponentAwareQueryInterface;
use Solarium\QueryType\Select\Query\Query;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SolrSearchController
 *
 * @package Drupal\eic_groups\Controller
 */
class SolrSearchController extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   */
  public function search(Request $request) {
    /** @var \Drupal\eic_search\Collector\SourcesCollector $sources_collector */
    $sources_collector = \Drupal::service('eic_search.sources_collector');
    $sources = $sources_collector->getSources();

    $source_class = $request->query->get('source_class');
    $search_value = $request->query->get('search_value', '');
    $current_group = $request->query->get('current_group');
    $topic_term_id = $request->query->get('topics');
    $facets_value = $request->query->get('facets_value');
    $sort_value = $request->query->get('sort_value');
    $facets_options = $request->query->get('facets_options');
    $facets_value = json_decode($facets_value, TRUE) ?: [];
    // timestamp value, if nothing set "*" (the default value on solr).
    $from_date = $request->query->get('from_date', '*');
    $end_date = $request->query->get('end_date', '*');
    $source = NULL;

    $facets_interests = [];

    if (array_key_exists('interests', $facets_value)) {
      $facets_interests = $facets_value['interests'];
      unset($facets_value['interests']);
    }

    $page = $request->query->get('page');
    $datasources = json_decode($request->query->get('datasource'), TRUE);
    $offset = $request->query->get('offset', SourceTypeInterface::READ_MORE_NUMBER_TO_LOAD);
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $index_storage->load('global');

    /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $backend */
    $backend = $index->getServerInstance()->getBackend();
    $config = $backend->getConfiguration();
    $backend->setConfiguration($config);
    /** @var \Drupal\search_api_solr\Plugin\SolrConnector\BasicAuthSolrConnector $connector */
    $connector = $backend->getSolrConnector();
    $solariumQuery = $connector->getSelectQuery();

    $spell_check = $solariumQuery->getSpellcheck();
    $spell_check->setDictionary('en');
    $spell_check->setAccuracy(0.5);
    $spell_check->setQuery($search_value);
    $spell_check->setCollate(TRUE);
    $spell_check->setReload(TRUE);
    $solariumQuery->setComponent(ComponentAwareQueryInterface::COMPONENT_SPELLCHECK, $spell_check);

    $content_type_query = '';
    $author_ignore_content_query = '';

    if ($source_class) {
      /** @var \Drupal\eic_search\Search\Sources\SourceTypeInterface $source */
      $source = array_key_exists($source_class, $sources) ? $sources[$source_class] : NULL;
      $search_fields_id = $source ? $source->getSearchFieldsId() : NULL;
      $search_query_value = $search_value ? "*$search_value*" : '*';

      $query_fields = [];
      $query_fields_string = '';

      foreach ($search_fields_id as $search_field_id) {
        $query_fields[] = "$search_field_id:$search_query_value";
        $query_fields_string = '(' . implode(' OR ', $query_fields) . ')';
      }

      if (
        $current_group &&
        !$source->excludingCurrentGroup() &&
        $group_id_fields = $source->getPrefilteredGroupFieldId()
      ) {
        $group_query = [];
        foreach ($group_id_fields as $group_id_field) {
          $group_query[] = "$group_id_field:($current_group)";
        }
        $group_query_string = '(' . implode(' OR ', $group_query) . ')';
        $query_fields_string .= empty($query_fields_string) ?
          "$group_query_string" :
          " AND $group_query_string";
      }

      // Handle current term ID sub-query.
      if ($topic_term_id) {
        $term_id_fields = $source->getPrefilteredTopicsFieldId();
        $term_query = [];
        foreach ($term_id_fields as $term_id_field) {
          $term_query[] = "$term_id_field:($topic_term_id)";
        }
        $term_query_string = '(' . implode(' OR ', $term_query) . ')';
        $query_fields_string .= empty($query_fields_string) ?
          "$term_query_string" :
          " AND $term_query_string";
      }

      if ($content_types = $source->getPrefilteredContentType()) {
        $allowed_content_type = implode(' OR ', $content_types);
        $content_type_query = ' AND (' . SourceTypeInterface::SOLR_FIELD_CONTENT_TYPE_ID . ':(' . $allowed_content_type . '))';
      }

      if ($source->ignoreContentFromCurrentUser()) {
        $author_ignore_content_query = ' AND !(' . $source->getAuthorFieldId() . ':' . $this->currentUser()->id() . ')';
      }

      if ($source->prefilterByCurrentUser() && $source->getAuthorFieldId()) {
        $prefilter_current_user_query =
          ' AND (' . $source->getAuthorFieldId() . ':(' . $this->currentUser()->id() . '))';
      }

      // If source supports date filter and query has a from or to date.
      if ($source->supportDateFilter() && ($from_date || $end_date)) {
        $date_fields_id = $source->getDateIntervalField();
        $date_from_id = $date_fields_id['from'];
        $date_end_id = $date_fields_id['to'];

        // If user only selected one day, we will only filter on the start date.
        // for eg: user select on widget 23-11-2021 - 23-11-2021 (double click)
        // we only do a query from 23-11-2021 to *.
        $end_date = $end_date === $from_date ? '*' : $end_date;
        $dates_query = [];

        $dates_query[] = "($date_from_id:[$from_date TO $end_date] AND $date_end_id:[$from_date TO $end_date])";
        $dates_query[] = "($date_from_id:[* TO $end_date] AND $date_end_id:[$from_date TO $end_date])";
        $dates_query[] = "($date_from_id:[$from_date TO $end_date] AND $date_end_id:[$end_date TO *])";
        $dates_query[] = "($date_from_id:[* TO $from_date] AND $date_end_id:[$end_date TO *])";
        $date_query = implode(' OR ', $dates_query);

        $date_query = "($date_query)";
        $query_fields_string .= empty($query_fields_string) ?
          "$date_query" :
          " AND $date_query";
      }

      $fields_filter_empty = $source->getFieldsToFilterEmptyValue();

      if (!empty($fields_filter_empty)) {
        foreach ($fields_filter_empty as $field) {
          $query_fields_string .= empty($query_fields_string) ?
            "$field:[* TO *]" :
            " AND $field:[* TO *]";
        }
      }

      if (!empty($query_fields_string)) {
        $solariumQuery->addParam('q', $query_fields_string);
      }
    }

    $solariumQuery->addParam('json.nl', 'arrarr');
    $solariumQuery->addParam('facet.field', $facets_options);
    $solariumQuery->addParam('facet.mincount', 1);
    $solariumQuery->addParam('facet', 'on');
    $solariumQuery->addParam('facet.sort', 'false');
    $solariumQuery->addParam('wt', 'json');

    if ($sort_value) {
      $sorts = explode('__', $sort_value);

      //Normally sort key have this structure 'FIELD__ASC' but add double check
      if (2 === count($sorts)) {
        $solariumQuery->addSort($sorts[0], $sorts[1]);
      }
    }

    $default_sort = $source->getSecondDefaultSort();

    if (!empty($default_sort) && 2 === count($default_sort)) {
      $solariumQuery->addSort($default_sort[0], $default_sort[1]);
    }

    // If there are no current sorts check if source has a default sort.
    if (
      !$sort_value &&
      $source instanceof SourceTypeInterface &&
      $default_sort = $source->getDefaultSort()
    ) {
      $solariumQuery->addSort($default_sort[0], $default_sort[1]);
    }

    $datasources_query = [];

    foreach ($datasources as $datasource) {
      $datasources_query[] = 'ss_search_api_datasource:"entity:' . $datasource . '"';
    }

    $fq = '(' . implode(' OR ', $datasources_query) . ')';

    $facets_query = $this->getFacetsQuery($facets_value);

    if ($facets_query) {
      $fq .= $facets_query;
    }

    if (
      $source instanceof SourceTypeInterface &&
      $current_group &&
      $source->prefilterByGroupVisibility()
    ) {
      $this->generateUsersQueryVisibilityGroup($fq, $current_group);
    }

    if (
      $source instanceof SourceTypeInterface &&
      $current_group &&
      $source->excludingCurrentGroup()
    ) {
      $this->generateExcludingUsersGroupQuery($fq, $current_group);
    }

    $this->generateQueryInterests($fq, $facets_interests);
    $this->generateQueryUserGroupsAndContents($fq, $facets_interests, $source);
    $this->generateQueryPrivateContent($fq);
    $this->generateQueryPublishedState($fq, $source);
    $this->generateQueryPager($solariumQuery, $page, $offset, $source);

    if ($content_type_query) {
      $fq .= $content_type_query;
    }

    if ($author_ignore_content_query) {
      $fq .= $author_ignore_content_query;
    }

    if ($prefilter_current_user_query) {
      $fq .= $prefilter_current_user_query;
    }

    if ($source->prefilterByGroupsMembership()) {
      /** @var \Drupal\group\GroupMembershipLoader $grp_membership_service */
      $grp_membership_service = \Drupal::service('group.membership_loader');
      $grps = $grp_membership_service->loadByUser($this->currentUser());

      $grp_ids = array_map(function (GroupMembership $grp_membership) {
        return $grp_membership->getGroup()->id();
      }, $grps);

      $group_filters_id = $source->getPrefilteredGroupFieldId();

      if ($group_filters_id) {
        $fq .= ' AND (' . reset($group_filters_id) . ':(' . implode(' OR ', $grp_ids) . ' OR "-1"))';
      }
    }

    $solariumQuery->addParam('fq', $fq);

    if (
      $index->isValidProcessor('group_content_access') &&
      GroupAccessContent::supportsIndex($index)
    ) {
      $index->getProcessor('group_content_access')
        ->preprocessSolrSearchQuery($solariumQuery);
    }

    $results = $connector->search($solariumQuery)->getBody();

    return new Response($results, Response::HTTP_OK, [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ]);
  }

  /**
   * @param array $facets_value
   *
   * @return string
   */
  private function getFacetsQuery($facets_value): string {
    $facets_query = '';

    foreach ($facets_value as $key => $facet_value) {
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

        $facets_query .= ' AND ' . $key . ':' . $query_values;
      }
    }

    return $facets_query;
  }

  /**
   * Generate query for user interests matching by their topics.
   *
   * @param string $fq
   * @param array $interests
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function generateQueryInterests(string &$fq, array $interests) {
    if (
      empty($interests) ||
      !array_key_exists('my_interests', $interests) ||
      !$interests['my_interests']
    ) {
      return;
    }

    $user_id = \Drupal::currentUser()->id();
    $profiles = \Drupal::entityTypeManager()
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
    $fq .= " AND (itm_group_field_vocab_topics:($user_topics_string) OR itm_content_field_vocab_topics:($user_topics_string) OR itm_message_node_ref_field_vocab_topics:($user_topics_string))";
  }

  /**
   * Generate query for user's groups and content.
   *
   * @param string $fq
   *  The field query stringify to send to SOLR
   * @param array $interests
   *  Values of interests facet
   * @param SourceTypeInterface $source
   *  The current source.
   */
  private function generateQueryUserGroupsAndContents(string &$fq, array $interests, SourceTypeInterface $source) {
    if (
      empty($interests) ||
      !array_key_exists('my_groups', $interests) ||
      !$interests['my_groups']
    ) {
      return;
    }

    $user_id = \Drupal::currentUser()->id();
    $user = User::load($user_id);

    /** @var \Drupal\group\GroupMembershipLoader $group_membership_service */
    $group_membership_service = \Drupal::service('group.membership_loader');
    $groups_membership = $group_membership_service->loadByUser($user);

    if ($groups_membership) {
      $groups_membership_id = array_map(function (GroupMembership $group_membership) {
        return $group_membership->getGroup()->id();
      }, $groups_membership);
    }

    $groups_membership_string = $groups_membership_id ? implode(' OR ', $groups_membership_id) : -1;
    $group_field_id = $source->getPrefilteredGroupFieldId();
    $group_field_id = reset($group_field_id);

    $fq .= " AND ($group_field_id:($groups_membership_string) OR its_global_group_parent_id:($groups_membership_string) OR its_content_uid:($groups_membership_string))";
  }

  /**
   * @param string $fq
   */
  private function generateQueryPrivateContent(string &$fq) {
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();

    if (
      in_array(UserHelper::ROLE_TRUSTED_USER, $roles) ||
      UserHelper::isPowerUser($current_user)
    ) {
      return;
    }

    $fq .= " AND bs_content_is_private:false";
  }

  /**
   * Add the status query to the query but check for groups if need
   * to show draft/pending for group owner.
   *
   * @param string $fq
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $source
   */
  private function generateQueryPublishedState(string &$fq, SourceTypeInterface $source) {
    if (!$source instanceof SourceTypeInterface) {
      return;
    }

    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $is_power_user = UserHelper::isPowerUser($current_user);

    // We need to show all groups on the groups overview for power users,
    // disregarding the published status.
    if ($source instanceof GroupSourceType && $is_power_user) {
      return;
    }

    $status_query = ' AND (bs_global_status:true';

    if ($source instanceof GroupSourceType) {
      $status_query .= ' OR (its_group_owner_id:' . $user_id . ')';
    }

    $status_query .= ')';

    $fq .= $status_query;

    // If it's not a power user or a group owner, add the filter query for published group parent.
    if (!$is_power_user) {
      $fq .= " AND (its_global_group_parent_published:1 OR its_group_owner_id:$user_id)";
    }
  }

  /**
   * Set the current start and offset.
   *
   * @param \Solarium\QueryType\Select\Query\Query $solariumQuery
   * @param int $page
   * @param int $offset
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface|null $source
   */
  private function generateQueryPager(Query &$solariumQuery, int $page, int $offset, ?SourceTypeInterface $source) {
    $solariumQuery->setRows($offset);

    //Default value will be to work like pagination.
    if ($source instanceof SourceTypeInterface && $source->allowPagination()) {
      $solariumQuery->setStart(($page * $offset) - $offset);
      return;
    }

    //If no pagination, it's a load more so we start at 1.
    $solariumQuery->setStart(0);
    $solariumQuery->setRows($offset * $page);
  }

  /**
   * Prefilter users by the current group visibility.
   *
   * @param $fq
   * @param $group_id
   */
  private function generateUsersQueryVisibilityGroup(&$fq, $group_id) {
    $query = '';
    $group = Group::load($group_id);

    /** @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorage $group_visibility_storage */
    $group_visibility_storage = \Drupal::service('oec_group_flex.group_visibility.storage');
    $group_visibility_entity = $group_visibility_storage->load($group->id());
    $visibility_type = $group_visibility_entity ?
      $group_visibility_entity->getType() :
      NULL;

    switch ($visibility_type) {
      case GroupVisibilityType::GROUP_VISIBILITY_PRIVATE:
      case GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY:
        $query = '(sm_user_profile_role_array:*' . UserHelper::ROLE_TRUSTED_USER . '*)';
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

    // Ignore anonymous user.
    $condition_ignore_anon = "!(its_user_id:0)";

    if (empty($query)) {
      $query = $condition_ignore_anon;
    }

    if (!empty($fq)) {
      $fq .= " AND $query AND $condition_ignore_anon";
      return;
    }

    $fq .= "$query AND $condition_ignore_anon";
  }

  /**
   * Prefilter users by excluding the current group.
   *
   * @param $fq
   * @param $group_id
   */
  private function generateExcludingUsersGroupQuery(&$fq, $group_id) {
    if (!empty($fq)) {
      $fq .= " AND !(itm_user__group_content__uid_gid:($group_id))";
      return;
    }

    $fq .= "!itm_user__group_content__uid_gid:($group_id)";
  }

}
