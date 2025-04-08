<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
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
   * Returns number of nodes of given content type grouped by vocabulary.
   */
  public function getNodesOfBundlePerTerm($bundle, $taxonomyField): array {
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node__' . $taxonomyField, 'tf', 'n.nid = tf.entity_id');
    $query->addExpression('COUNT(tf.' . $taxonomyField . '_target_id)', 'count_nodes');
    $query->addExpression('tf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('n.type', $bundle);
    $query->groupBy('taxonomy_term_id');
    $query->orderBy('taxonomy_term_id');
    $results = $query->execute()->fetchAll();

    // Prepare data for JSON.
    $data = [];
    foreach ($results as $row) {
      $data[] = [
        'name' => $this->entityTypeManager->getStorage('taxonomy_term')->load($row->taxonomy_term_id)?->name->value ?? 'NA',
        'y' => (int) $row->count_nodes,
      ];
    }

    return $data;
  }
}
