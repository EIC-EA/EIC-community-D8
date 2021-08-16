<?php

namespace Drupal\eic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\eic_user\UserHelper;
use Drupal\group\GroupMembership;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Solarium\Component\ComponentAwareQueryInterface;
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
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   */
  public function search(Request $request) {
    /** @var \Drupal\eic_search\Collector\SourcesCollector $sources_collector */
    $sources_collector = \Drupal::service('eic_search.sources_collector');
    $sources = $sources_collector->getSources();

    $source_class = $request->query->get('source_class');
    $search_value = $request->query->get('search_value');
    $current_group = $request->query->get('current_group');
    $facets_value = $request->query->get('facets_value');
    $sort_value = $request->query->get('sort_value');
    $facets_options = $request->query->get('facets_options');
    $facets_value = json_decode($facets_value, TRUE);
    $source = NULL;

    $facets_interests = [];

    if (array_key_exists('interests', $facets_value)) {
      $facets_interests = $facets_value['interests'];
      unset($facets_value['interests']);
    }

    $page = $request->query->get('page');
    $datasources = json_decode($request->query->get('datasource'), TRUE);
    $offset = $request->query->get('offset');
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $index_storage->load('global');

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

    if ($source_class) {
      /** @var \Drupal\eic_search\Search\Sources\SourceTypeInterface $source */
      $source = array_key_exists($source_class, $sources) ? $sources[$source_class] : NULL;
      $search_fields_id = $source ? $source->getSearchFieldsId() : NULL;
      $search_query_value = $search_value ? "*$search_value*" : '*';

      $query_fields = [];

      foreach ($search_fields_id as $search_field_id) {
        $query_fields[] = "$search_field_id:$search_query_value";
      }

      $query_fields_string = implode(' OR ', $query_fields);
      if ($current_group) {
        $group_id_field = $source->getPrefilteredGroupFieldId();
        $query_fields_string .= empty($query_fields_string) ?
          "$group_id_field:($current_group)" :
          " AND ($group_id_field:($current_group))";
      }

      if ($content_types = $source->getPrefilteredContentType()) {
        $allowed_content_type = implode(' OR ', $content_types);
        $content_type_query = ' AND (' . SourceTypeInterface::SOLR_FIELD_CONTENT_TYPE_ID . ':(' . $allowed_content_type . '))';
      }

      $solariumQuery->addParam('q', $query_fields_string);
    }

    $solariumQuery->addParam('json.nl', 'arrarr');
    $solariumQuery->addParam('facet.field', $facets_options);
    $solariumQuery->addParam('facet', 'on');
    $solariumQuery->addParam('facet.sort', 'false');
    $solariumQuery->setStart(($page * $offset) - $offset);
    $solariumQuery->setRows($page * $offset);
    $solariumQuery->addParam('wt', 'json');

    if ($sort_value) {
      $sorts = explode('__', $sort_value);

      //Normally sort key have this structure 'FIELD__ASC' but add double check
      if (2 === count($sorts)) {
        $solariumQuery->addSort($sorts[0], $sorts[1]);
      }
    }

    //If there are no current sorts check if source has a default sort
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

    $this->generateQueryInterests($fq, $facets_interests);
    $this->generateQueryUserGroupsAndContents($fq, $facets_interests);
    $this->generateQueryPrivateContent($fq);
    $this->generateQueryPublishedState($fq, $source);

    if ($content_type_query) {
      $fq .= $content_type_query;
    }

    $solariumQuery->addParam('fq', $fq);

    if ($index->isValidProcessor('group_content_access')) {
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
        $facets_query .= ' AND ' . $key . ':"' . implode(' OR ', $values) . '"';
      }
    }

    return $facets_query;
  }

  /**
   * Generate query for user interests matching by their topics
   *
   * @param string $fq
   *  The field query stringify to send to SOLR
   * @param array $interests
   *  Values of interests facet
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
    $user_topics = $profile->get('field_vocab_topic_interest')
      ->referencedEntities();
    $user_topics_id = [0];

    if ($user_topics) {
      $user_topics_id = array_map(function (Term $topic) {
        return $topic->id();
      }, $user_topics);
    }

    $user_topics_string = implode(' OR ', $user_topics_id);
    $fq .= " AND (itm_group_field_vocab_topics:($user_topics_string) OR itm_content_field_vocab_topics:($user_topics_string))";
  }

  /**
   * Generate query for user's groups and content
   *
   * @param string $fq
   *  The field query stringify to send to SOLR
   * @param array $interests
   *  Values of interests facet
   */
  private function generateQueryUserGroupsAndContents(string &$fq, array $interests) {
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

    $fq .= " AND (its_group_id_integer:($groups_membership_string) OR ss_global_group_parent_id:($groups_membership_string) OR its_content_uid:($groups_membership_string))";
  }

  /**
   * @param $fq
   */
  private function generateQueryPrivateContent(&$fq) {
    $roles = \Drupal::currentUser()->getRoles();

    if (in_array(UserHelper::ROLE_TRUSTED_USER, $roles)) {
      return;
    }

    $fq .= " AND bs_content_is_private:false";
  }

  /**
   * Add the status query to the query but check for groups if need
   * to show draft/pending for group owner
   *
   * @param $fq
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $source
   */
  private function generateQueryPublishedState(&$fq, SourceTypeInterface $source) {
    if (!$source instanceof SourceTypeInterface) {
      return;
    }

    $status_query = ' AND (bs_global_status:true';

    if ($source instanceof GroupSourceType) {
      $user_id = \Drupal::currentUser()->id();
      $status_query .= ' OR (its_group_owner_id:' . $user_id . ')';
    }

    $status_query .= ')';

    $fq .= $status_query;
  }

}
