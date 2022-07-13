<?php

namespace Drupal\eic_topics;

use Drupal\Core\Url;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\Search\Sources\GlobalEventSourceType;
use Drupal\eic_search\Search\Sources\GlobalSourceType;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\OrganisationSourceType;
use Drupal\eic_search\Search\Sources\UserGallerySourceType;
use Drupal\eic_search\SearchHelper;
use Drupal\eic_search\Service\SolrSearchManager;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Class TopicsManager
 */
class TopicsManager {

  const FIELD_ENTITY_TOPICS = 'field_vocab_topics';

  const FIELD_PROFILE_TOPIC_EXPERTISE = 'field_vocab_topic_expertise';

  /**
   * The EIC user helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * @var \Drupal\eic_search\Service\SolrSearchManager
   */
  private $searchManager;

  /**
   * Constructs a new UserHelper.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC user helper service.
   * @param SolrSearchManager $solr_search_manager
   *   The solr search manager service.
   */
  public function __construct(UserHelper $user_helper, SolrSearchManager $solr_search_manager) {
    $this->userHelper = $user_helper;
    $this->searchManager = $solr_search_manager;
  }

  /**
   * @param string $tid
   *
   * @return array
   */
  public function generateTopicsStats(string $tid): array {
    $term = Term::load($tid);

    return [
      'stories' => [
        'stat' => $this->getStatBySolr($tid, GlobalSourceType::class, 'story'),
        'url' => $this->getNodeRedirectUrl($term, 'story'),
      ],
      'wiki_page' => [
        'stat' => $this->getStatBySolr($tid, GlobalSourceType::class, 'wiki_page'),
        'url' => $this->getNodeRedirectUrl($term, 'wiki_page'),
      ],
      'discussion' => [
        'stat' => $this->getStatBySolr($tid, GlobalSourceType::class, 'discussion'),
        'url' => $this->getNodeRedirectUrl($term, 'discussion'),
      ],
      'news' => [
        'stat' => $this->getStatBySolr($tid, GlobalSourceType::class, 'news'),
        'url' => $this->getNodeRedirectUrl($term, 'news'),
      ],
      'group' => [
        'stat' => $this->getStatBySolr($tid, GroupSourceType::class),
        'url' => $this->getGroupRedirectUrl($term, 'group'),
      ],
      /** @TODO To define how media will be displayed in global search */
      'file' => [
        'stat' => $this->getStatByEntityType($tid, 'node', 'document'),
        'url' => '',
      ],
      'event' => [
        'stat' => $this->getStatBySolr($tid, GlobalEventSourceType::class),
        'url' => $this->getGroupRedirectUrl($term, 'event'),
      ],
      'expert' => [
        'stat' => $this->getStatBySolr($tid, UserGallerySourceType::class),
        'url' => $this->getUserRedirectUrl($term),
      ],
      'organisation' => [
        'stat' => $this->getStatBySolr($tid, OrganisationSourceType::class),
        'url' => $this->getGroupRedirectUrl($term, 'organisation'),
      ],
    ];
  }

  /**
   * @param int $tid
   * @param string $entity_type
   * @param string $bundle
   *
   * @return int
   */
  private function getStatByEntityType(
    int $tid,
    string $entity_type,
    string $bundle
  ): int {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery($entity_type)
      ->condition('media' !== $entity_type ? 'type' : 'bundle', $bundle)
      ->condition(self::FIELD_ENTITY_TOPICS, $tid, 'IN')
      ->condition('status', 1);

    return $query->count()->execute();
  }

  /**
   * @param int $tid
   * @param string $source_class
   * @param string|null $content_type
   *
   * @return int|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   */
  private function getStatBySolr(int $tid, string $source_class, ?string $content_type = NULL) {
    $solr_query = $this->searchManager->init($source_class);
    $solr_query->buildPrefilterTopic($tid);
    if ($content_type) {
      $solr_query->buildSortFacets([Topics::CONTENT_TYPE_ID_FIELD_SOLR => [$content_type => TRUE]], NULL);
    }
    $results = $solr_query->search();
    $results = json_decode($results, TRUE);
    return !empty($results) ? $results['response']['numFound'] : 0;
  }

  /**
   * @param TermInterface $term
   * @param string $bundle
   *
   * @return string
   */
  private function getNodeRedirectUrl(
    TermInterface $term,
    string $bundle
  ): string {
    if (!$term instanceof TermInterface) {
      return '';
    }

    $filters = [
      Topics::CONTENT_TYPE_ID_FIELD_SOLR => $bundle,
      Topics::TERM_TOPICS_ID_FIELD_CONTENT_SOLR => $term->label(),
    ];

    $query_options = [
      'query' => SearchHelper::buildSolrQueryParams($filters),
    ];

    return Url::fromRoute(
      'eic_search.global_search',
      [],
      $query_options
    )->toString();
  }

  /**
   * @param TermInterface|NULL $term
   *
   * @return string
   */
  private function getUserRedirectUrl(?TermInterface $term): string {
    if (!$term instanceof TermInterface) {
      return '';
    }

    $filters = [
      'sm_user_profile_topic_expertise_string' => $term->label(),
    ];

    $query_options = [
      'query' => SearchHelper::buildSolrQueryParams($filters),
    ];

    return Url::fromRoute(
      'eic_search.people',
      [],
      $query_options
    )->toString();
  }

  /**
   * @param TermInterface|NULL $tid
   * @param string $group_type
   *
   * @return string
   */
  private function getGroupRedirectUrl(
    ?TermInterface $term,
    string $group_type
  ): string {
    if (!$term instanceof TermInterface) {
      return '';
    }

    $route_name = '';
    $route_parameters = [];
    $solr_topic_field_id = '';

    switch ($group_type) {
      case 'group':
        $route_name = 'eic_search.groups';
        $solr_topic_field_id = Topics::TERM_TOPICS_ID_FIELD_GROUP_SOLR;
        break;

      case 'event':
        $route_name = 'eic_search.events';
        $solr_topic_field_id = Topics::TERM_TOPICS_ID_FIELD_GROUP_SOLR;
        break;

      case 'organisation':
        $url = GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::ORGANISATIONS)->getUrl();
        $route_name = $url->getRouteName();
        $route_parameters = $url->getRouteParameters();
        $solr_topic_field_id = Topics::TERM_TOPICS_ID_FIELD_GROUP_SOLR;
        break;
    }

    if (!$route_name || !$solr_topic_field_id) {
      return '';
    }

    $filters = [
      $solr_topic_field_id => $term->label(),
    ];

    $query_options = [
      'query' => SearchHelper::buildSolrQueryParams($filters),
    ];

    return Url::fromRoute(
      $route_name,
      $route_parameters,
      $query_options
    )->toString();
  }

  /**
   * Return TRUE if the current page is a taxonomy term.
   *
   * @return bool
   */
  public static function isTopicPage(): bool {
    // @todo Refactor this to check the bundle of the term (should be Topics).
    return 'entity.taxonomy_term.canonical' === \Drupal::routeMatch()
        ->getRouteName();
  }

}
