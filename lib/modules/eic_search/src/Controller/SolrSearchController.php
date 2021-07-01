<?php

namespace Drupal\eic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\group\GroupMembership;
use Drupal\user\Entity\User;
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
    $facets_value = $request->query->get('facets_value');
    $sort_value = $request->query->get('sort_value');
    $facets_options = $request->query->get('facets_options');
    $facets_value = json_decode($facets_value, TRUE);
    $page = $request->query->get('page');
    $datasource = $request->query->get('datasource');
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

    if ($source_class) {
      /** @var \Drupal\eic_search\Search\Sources\SourceTypeInterface $source */
      $source = array_key_exists($source_class, $sources) ? $sources[$source_class] : NULL;
      $search_fields_id = $source ? $source->getSearchFieldsId() : NULL;
      $search_query_value = $search_value ? "*$search_value*" : '*';

      $query_fields = [];

      foreach ($search_fields_id as $search_field_id) {
        $query_fields[] = "$search_field_id:$search_query_value";
      }

      $solariumQuery->addParam('q', implode(' OR ', $query_fields));
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

    $visibility_condition = ' AND ' . $this->buildGroupVisibilityQuery();
    $fq = 'ss_search_api_datasource:"entity:' . $datasource . '"';

    if ($datasource === 'group') {
      $fq .= $visibility_condition;
    }

    $facets_query = $this->getFacetsQuery($facets_value);

    if ($facets_query) {
      $fq .= $facets_query;
    }

    $solariumQuery->addParam('fq', $fq);
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
   * Create the query string for SOLR to match with group visibility
   *
   * @return string
   */
  private function buildGroupVisibilityQuery() {
    $user_id = \Drupal::currentUser()->id();

    $user = User::load($user_id);
    $email = explode('@', $user->getEmail());
    $domain = array_pop($email) ?: 0;

    /** @var \Drupal\group\GroupMembershipLoader $group_membership_service */
    $group_membership_service = \Drupal::service('group.membership_loader');
    $groups = $group_membership_service->loadByUser($user);

    $group_ids = array_map(function (GroupMembership $group_membership) {
      return $group_membership->getGroup()->id();
    }, $groups);

    // If group is private, the user needs to be in group to view it
    $group_ids_formatted = !empty($group_ids) ? implode(' OR ', $group_ids) : 0;

    $query = '
    ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC . '
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE . ' AND its_group_id:(' . $group_ids_formatted . '))
    OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . ':*' . $domain . '*)
    ';

    // Restricted community group, only trusted_user role can view
    if (!$user->isAnonymous() && $user->hasRole('trusted_user')) {
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY . ')';
    }

    // Trusted users restriction
    if (!$user->isAnonymous()) {
      $username = $user->getAccountName();
      $query .= ' OR (ss_group_visibility:' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ' AND ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . ':*' . "$user_id|$username" . '*)';
    }

    return "($query)";
  }

}
