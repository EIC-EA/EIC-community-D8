<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
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
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $aliasManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    AliasManagerInterface $aliasManager,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('path_alias.manager'),
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
   * created in the past 30 days.
   */
  public function getNumberOfBundleNodesPast30Days($bundle): array|int {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->condition('n.type', $bundle)
      ->condition('nfd.created', strtotime('-30 days'), '>=');

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
      echo $parentTermId;
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
          'name' => $this->entityTypeManager->getStorage('taxonomy_term')->load($row->taxonomy_term_id)?->name->value ?? 'NA',
          'y' => (int) $row->count_nodes,
        ];
      }
    } else if ($chartType == 'column') {
      foreach ($results as $row) {
        $label = $this->entityTypeManager->getStorage('taxonomy_term')->load($row->taxonomy_term_id)?->name->value ??
          'NA';
        $data[$row->taxonomy_term_id]['id'] = $label;
        $data[$row->taxonomy_term_id]['label'] = $label;
        $data[$row->taxonomy_term_id]['count'] = $row->count_nodes;
      }
    }

    return $data;
  }

  /**
   * Returns most viewed nodes of given bundle.
   */
  public function getMostViewedNodesOfBundle($bundle): array {
    $query = $this->connection->select('node_counter', 'nc');
    $query->innerJoin('node', 'n', 'n.nid = nc.nid');
    $query->addExpression('nc.nid', 'node_id');
    $query->addExpression('nc.totalcount', 'total_views');
    $query->condition('n.type', $bundle);
    $query->orderBy('total_views', 'DESC');
    $query->range(0, 5);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $row) {
      $data[] = [
        'prefix' => (int) $row->total_views . ' views',
        'title' => $this->entityTypeManager->getStorage('node')->load($row->node_id)->label(),
        'url' => $this->aliasManager->getAliasByPath('/node/' . $row->node_id),
      ];
    }

    return $data;
  }
}
