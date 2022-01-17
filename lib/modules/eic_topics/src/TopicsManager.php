<?php

namespace Drupal\eic_topics;

use Drupal\Core\Url;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\SearchHelper;
use Drupal\eic_topics\Constants\Topics;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Class TopicsManager
 */
class TopicsManager {

  const FIELD_ENTITY_TOPICS = 'field_vocab_topics';

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
      /** @TODO not existing yet */
      'expert' => [
        'stat' => 0,
        'url' => '',
      ],
      /** @TODO not existing yet */
      'organization' => [
        'stat' => 0,
        'url' => '',
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
    // @todo This should be ideally based on the same source (eic_search) as the
    //   overview pages to avoid discrepancies in amount of results.
    $query = \Drupal::entityQuery($entity_type)
      ->condition('media' !== $entity_type ? 'type' : 'bundle', $bundle)
      ->condition(self::FIELD_ENTITY_TOPICS, $tid, 'IN');

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

    // Depending on the bundle, we might have different URLs.
    switch ($bundle) {
      case 'news':
      case 'story':
        $url = GlobalOverviewPages::getGlobalOverviewPageUrl(GlobalOverviewPages::NEWS_STORIES);
        break;

      default:
        $url = Url::fromRoute('eic_search.global_search');
        break;
    }

    $url->setOptions($query_options);

    return $url->toString();
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

    $query_options = [
      'query' => [
        'filter' => Topics::TERM_TOPICS_ID_FIELD_USER_SOLR . ':' . $term->label(
          ),
      ],
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
    }

    if (!$route_name || !$solr_topic_field_id) {
      return '';
    }

    $query_options = [
      'query' => [
        'filter' => $solr_topic_field_id . ':' . $term->label(),
      ],
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
    return 'entity.taxonomy_term.canonical' === \Drupal::routeMatch()
        ->getRouteName();
  }

}
