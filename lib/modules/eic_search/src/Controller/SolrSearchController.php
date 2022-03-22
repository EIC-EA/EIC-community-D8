<?php

namespace Drupal\eic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_search\Plugin\search_api\processor\GroupAccessContent;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\eic_search\Service\SolrSearchManager;
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
   * @var \Drupal\eic_search\Service\SolrSearchManager
   *   The solr search manager service.
   */
  private SolrSearchManager $searchManager;

  public function __construct(SolrSearchManager $solr_search_manager) {
    $this->searchManager = $solr_search_manager;
  }

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

    $search = $this->searchManager->init($source_class, $facets_options);
    $search->

    return new Response(, Response::HTTP_OK, [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ]);
  }

}
