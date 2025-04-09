<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Content statistics class.
 */
class ContentStatistics implements ContentStatisticsInterface {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardHelperInterface
   */
  protected DashboardHelperInterface $dashboardHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    DashboardHelperInterface $dashboardHelper,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->dashboardHelper = $dashboardHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('eic_dashboards.helper'),
    );
  }

  /**
   * Returns number of content of given bundle.
   */
  public function getNumberOfBundleNodes($bundle): array|int {
    $query = $this->connection->select('node', 'n');
    $query->condition('n.type', $bundle);

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns number of content of given bundle
   * created in the past days.
   */
  public function getNumberOfBundleNodesPastDays($bundle, $days = 30): array|int {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->condition('n.type', $bundle)
      ->condition('nfd.created', strtotime('-' . $days . ' days'), '>=');

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns number of nodes of given bundle grouped by terms.
   * If $parentTermId is given, it will display only the children terms.
   */
  public function getNodesOfBundlePerTerm($bundle, $taxonomyField, $chartType, $parentTermId): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node__' . $taxonomyField, 'ntf', 'n.nid = ntf.entity_id');

    if ($parentTermId) {
      $query->innerJoin('taxonomy_term__parent', 'ttp', 'ntf.' . $taxonomyField . '_target_id = ttp.entity_id');
    }

    $query->addExpression('COUNT(ntf.' . $taxonomyField . '_target_id)', 'count_nodes');
    $query->addExpression('ntf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('n.type', $bundle);

    if ($parentTermId) {
      $query->condition('ttp.parent_target_id', $parentTermId);
    }

    $query->groupBy('taxonomy_term_id');
    $query->orderBy('taxonomy_term_id');
    $results = $query->execute()->fetchAll();

    $data = [];

    // Handle data output depending on chart type.
    if ($chartType == 'pie') {
      foreach ($results as $row) {
        $data[] = [
          'name' => $this->dashboardHelper->getTaxonomyTermLabel($row->taxonomy_term_id) ?? 'NA',
          'y' => (int) $row->count_nodes,
        ];
      }
    } else if ($chartType == 'column') {
      foreach ($results as $row) {
        $label = $this->dashboardHelper->getTaxonomyTermLabel($row->taxonomy_term_id)?->name->value ?? 'NA';
        $data[$row->taxonomy_term_id]['id'] = $label;
        $data[$row->taxonomy_term_id]['label'] = $label;
        $data[$row->taxonomy_term_id]['count'] = $row->count_nodes;
      }
    }

    return $data;
  }

  /**
   * Returns information about last story nodes.
   */
  public function getLastStoriesMetrics($range = 10): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->innerJoin('node__field_vocab_story_type', 'nfvst', 'n.nid = nfvst.entity_id');
    $query->innerJoin('node_counter', 'nc', 'n.nid = nc.nid');
    $query->innerJoin('flag_counts', 'fc', 'n.nid = fc.entity_id');
    $query->addExpression('n.nid', 'node_id');
    $query->addExpression('nfd.title', 'title');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(nfd.created), '%d %M %Y')", 'created');
    $query->addExpression('nfvst.field_vocab_story_type_target_id', 'taxonomy_term_id');
    $query->addExpression('fc.count', 'likes');
    $query->addExpression('nc.totalcount', 'views');
    $query->condition('n.type', 'story')
      ->condition('fc.entity_type', 'node')
      ->condition('fc.flag_id', 'like_content')
      ->orderBy('created', 'DESC')
      ->range(0, $range);
    $results = $query->execute()->fetchAll();

    $rows = [];

    foreach ($results as $result) {
      $rows[] = [
        'title' => Markup::create('<a href="/node/' . $result->node_id . '">' . $result->title . '</a>'),
        'published' => $result->created,
        'type' =>  $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id) ?? 'NA',
        'views' => $result->views,
        'likes' => $result->likes,
      ];
    }

    $header = [
      'Story',
      'Published',
      'Type',
      'Views',
      'Likes',
    ];

    return [
      'header' => $header,
      'rows' => $rows,
    ];
  }

  /**
   * Returns most viewed nodes of given bundle.
   */
  public function getMostViewedNodesOfBundle($bundle, $range = 5): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_counter', 'nc', 'n.nid = nc.nid');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->addExpression('nc.totalcount', 'total_views');
    $query->addExpression('n.nid', 'node_id');
    $query->addExpression('nfd.title', 'title');
    $query->condition('n.type', $bundle);
    $query->orderBy('total_views', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $row) {
      $data[] = [
        'prefix' => (int) $row->total_views . ' views',
        'title' => $row->title,
        'url' => '/node/' . $row->node_id,
      ];
    }

    return $data;
  }
}
