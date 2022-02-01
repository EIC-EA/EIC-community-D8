<?php

namespace Drupal\eic_topics;

use Drupal\Core\Url;
use Drupal\eic_search\SearchHelper;
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
   * Constructs a new UserHelper.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC user helper service.
   */
  public function __construct(UserHelper $user_helper) {
    $this->userHelper = $user_helper;
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
        'stat' => $this->getStatByEntityType($tid, 'node', 'story'),
        'url' => $this->getNodeRedirectUrl($term, 'story'),
      ],
      'wiki_page' => [
        'stat' => $this->getStatByEntityType($tid, 'node', 'wiki_page'),
        'url' => $this->getNodeRedirectUrl($term, 'wiki_page'),
      ],
      'discussion' => [
        'stat' => $this->getStatByEntityType($tid, 'node', 'discussion'),
        'url' => $this->getNodeRedirectUrl($term, 'discussion'),
      ],
      'news' => [
        'stat' => $this->getStatByEntityType($tid, 'node', 'news'),
        'url' => $this->getNodeRedirectUrl($term, 'news'),
      ],
      'group' => [
        'stat' => $this->getStatByEntityType($tid, 'group', 'group'),
        'url' => $this->getGroupRedirectUrl($term, 'group'),
      ],
      /** @TODO To define how media will be displayed in global search */
      'file' => [
        'stat' => $this->getStatByEntityType($tid, 'media', 'eic_document'),
        'url' => '',
      ],
      'event' => [
        'stat' => $this->getStatByEntityType($tid, 'group', 'event'),
        'url' => $this->getGroupRedirectUrl($term, 'event'),
      ],
      'expert' => [
        'stat' => count($this->userHelper->getUsersByExpertise($term)),
        'url' => $this->getUserRedirectUrl($term),
      ],
      'organisation' => [
        'stat' => $this->getStatByEntityType($tid, 'group', 'organisation'),
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
      Topics::TERM_TOPICS_ID_FIELD_USER_SOLR => $term->label(),
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
        // @todo Set the correct route name once available.
        $route_name = '';
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
      [],
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
