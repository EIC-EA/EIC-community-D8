<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 node view counter source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_node_view_counter",
 *   source_module = "eic_migrate"
 * )
 */
class NodeViewCounter extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Source data is queried from 'node_counter' table.
    $query = $this->select('node_counter', 'n');
    $query->fields('n', [
      'nid',
      'totalcount',
      'daycount',
      'timestamp',
    ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'totalcount' => $this->t('The total number of times the node has been viewed'),
      'daycount' => $this->t('The total number of times the node has been viewed today'),
      'timestamp' => $this->t('The most recent time the node has been viewed'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

}
