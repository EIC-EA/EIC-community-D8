<?php

namespace Drupal\eic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
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
        $query_fields_string .= " AND ($group_id_field:($current_group))";
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

    $datasources_query = [];

    foreach ($datasources as $datasource) {
      $datasources_query[] = 'ss_search_api_datasource:"entity:' . $datasource . '"';
    }

    $fq = implode(' OR ', $datasources_query);

    $facets_query = $this->getFacetsQuery($facets_value);

    if ($facets_query) {
      $fq .= $facets_query;
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

}
