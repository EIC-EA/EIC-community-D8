<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Markup;
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
    DashboardHelperInterface $dashboardHelper,
  ) {
    $this->connection = $connection;
    $this->dashboardHelper = $dashboardHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
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
   */
  public function getNodesOfBundlePerTerm($bundle, $taxonomyField, $chartType, $range = NULL): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node__' . $taxonomyField, 'ntf', 'n.nid = ntf.entity_id');
    $query->addExpression('COUNT(ntf.' . $taxonomyField . '_target_id)', 'nodes_count');
    $query->addExpression('ntf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('n.type', $bundle);

    if ($range) {
      $query->range(0, $range);
    }

    $query->groupBy('taxonomy_term_id');
    $query->orderBy('nodes_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    // Handle data output depending on chart type.
    if ($chartType == 'pie') {
      foreach ($results as $result) {
        $data[] = [
          'name' => $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id) ?? 'NA',
          'y' => (int) $result->nodes_count,
        ];
      }
    } else if ($chartType == 'column') {
      foreach ($results as $result) {
        $label = $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id) ?? 'NA';
        $data[$result->taxonomy_term_id]['id'] = $label;
        $data[$result->taxonomy_term_id]['label'] = $label;
        $data[$result->taxonomy_term_id]['count'] = $result->nodes_count;
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
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(nfd.created), '%d %b %Y')", 'created');
    $query->addExpression('nfvst.field_vocab_story_type_target_id', 'taxonomy_term_id');
    $query->addExpression('fc.count', 'likes');
    $query->addExpression('nc.totalcount', 'views');
    $query->condition('n.type', 'story')
      ->condition('fc.entity_type', 'node')
      ->condition('fc.flag_id', 'like_content')
      ->orderBy('nfd.created', 'DESC')
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

    foreach ($results as $result) {
      $data[] = [
        'prefix' => (int) $result->total_views . ' views',
        'title' => $result->title,
        'url' => '/node/' . $result->node_id,
      ];
    }

    return $data;
  }

  /**
   * Returns most downloaded nodes of given bundle.
   */
  public function getMostDownloadedFilesOfBundle($bundle, $range = 5): array {
    $query = $this->connection->select('file_counter', 'fc');
    $query->innerJoin('media__field_media_file', 'mfmf', 'mfmf.field_media_file_target_id = fc.fid');
    $query->innerJoin('node__field_document_media', 'nfdm', 'nfdm.field_document_media_target_id = mfmf.entity_id');
    $query->innerJoin('node_field_data', 'nfd', 'nfd.nid = nfdm.entity_id');
    $query->addExpression('fc.totalcount', 'total_downloads');
    $query->addExpression('nfd.title', 'title');
    $query->addExpression('nfd.nid', 'node_id');
    $query->condition('nfd.type', $bundle);
    $query->orderBy('total_downloads', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[] = [
        'prefix' => (int) $result->total_downloads . ' downloads',
        'title' => $result->title,
        'url' => '/node/' . $result->node_id,
      ];
    }

    return $data;
  }

  /**
   * Returns latest nodes of given bundle
   */
  public function getLatestNodesOfBundle($bundle, $range = 10): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->addExpression('n.nid', 'node_id');
    $query->addExpression('nfd.title', 'title');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(nfd.created), '%d %b %Y')", 'created');
    $query->condition('n.type', $bundle);
    $query->orderBy('nfd.created', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[] = [
        'prefix' => $result->created,
        'title' => $result->title,
        'url' => '/node/' . $result->node_id,
      ];
    }

    return $data;
  }

  /**
   * Returns number of nodes of given bundle grouped by value from a list field.
   */
  public function getNodesOfBundlePerValue($bundle, $listField, $chartType, $range = NULL): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node__' . $listField, 'ntf', 'n.nid = ntf.entity_id');
    $query->addExpression('COUNT(ntf.' . $listField . '_value)', 'nodes_count');
    $query->addExpression('ntf.' . $listField . '_value', 'value');
    $query->condition('n.type', $bundle);

    if ($range) {
      $query->range(0, $range);
    }

    $query->groupBy('value');
    $query->orderBy('nodes_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];
    $entityTypeId = 'node';

    // Handle data output depending on chart type.
    if ($chartType == 'pie') {
      foreach ($results as $result) {
        $data[] = [
          'name' => $this->dashboardHelper->getListFieldValue($entityTypeId, $listField, $result->value),
          'y' => (int) $result->nodes_count,
        ];
      }
    } else if ($chartType == 'column') {
      foreach ($results as $result) {
        $label = $this->dashboardHelper->getListFieldValue($entityTypeId, $listField, $result->value);
        $data[$result->value]['id'] = $label;
        $data[$result->value]['label'] = $label;
        $data[$result->value]['count'] = $result->nodes_count;
      }
    }

    return $data;
  }

  /**
   * Returns most commented nodes of given bundle.
   */
  public function getMostCommentedNodesOfBundle($bundle, $range = 5): array {
    $query = $this->connection->select('comment_field_data', 'cfd');
    $query->innerJoin('node_field_data', 'nfd', 'nfd.nid = cfd.entity_id');
    $query->addExpression('COUNT(cfd.entity_id)', 'comments_count');
    $query->addExpression('cfd.entity_id', 'node_id');
    $query->addExpression('nfd.title', 'title');
    $query->condition('nfd.type', $bundle);
    $query->groupBy('node_id');
    $query->groupBy('title');
    $query->orderBy('comments_count', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[] = [
        'prefix' => (int) $result->comments_count . ' comments',
        'title' => $result->title,
        'url' => '/node/' . $result->node_id,
      ];
    }

    return $data;
  }

  /**
   * Returns list of groups based on number of bundle.
   */
  public function getGroupsByNumberOfBundle($bundle, $groupType, $range = NULL) {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('groups_field_data', 'gfd', 'gfd.id = gcfd.gid');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'nodes_count');
    $query->condition('gcfd.type', $bundle);

    if ($groupType) {
      $query->condition('gfd.type', $groupType);
    }

    $query->groupBy('group_id');
    $query->groupBy('label');
    $query->orderBy('nodes_count', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[$result->group_id]['id'] = $result->label;
      $data[$result->group_id]['label'] = $result->label;
      $data[$result->group_id]['count'] = $result->nodes_count;
    }

    return $data;
  }

  /**
   * Returns nodes of bundle created between given dates.
   */
  public function getNodesOfBundleInGivenPeriod($bundle, $startDate, $endDate, $range = 10): array {
    if (isset($startDate) && $startDate != "" && isset($endDate) && $endDate != "") {
      // Convert string dates to DateTime objects if necessary.
      if (is_string($startDate)) {
        $startDate = new \DateTime($startDate);
      }
      if (is_string($endDate)) {
        $endDate = new \DateTime($endDate);
      }

      // Ensure dates are at the start/end of their respective days.
      $startDate->setTime(0, 0, 0);
      $endDate->setTime(23, 59, 59);
    }

    // Build the query.
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->addExpression('n.nid', 'node_id');
    $query->addExpression('nfd.title', 'title');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(nfd.created), '%d %b %Y')", 'created');
    $query->condition('n.type', $bundle);
    if (isset($startDate) && $startDate != "" && isset($endDate) && $endDate != "") {
      $query->condition('nfd.created', [
        $startDate->getTimestamp(),
        $endDate->getTimestamp(),
      ], 'BETWEEN');
    }
    $query->orderBy('nfd.created', 'DESC');
    $query->range(0, $range);

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      return [];
    }

    $data = [];
    foreach ($results as $result) {
      $data[] = [
        'prefix' => $result->created,
        'title' => $result->title,
        'url' => '/node/' . $result->node_id,
      ];
    }

    return $data;
  }

}
