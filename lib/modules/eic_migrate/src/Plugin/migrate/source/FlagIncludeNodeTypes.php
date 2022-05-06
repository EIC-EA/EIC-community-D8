<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal 7 flag nodes source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_flag_include_node_types",
 *   source_module = "flag"
 * )
 */
class FlagIncludeNodeTypes extends Flag {

  /**
   * Array of included node types from d7.
   *
   * @var array
   */
  protected $includedNodeTypes = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    StateInterface $state,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    $this->includedNodeTypes = !empty($configuration['constants']['included_node_types']) ? $configuration['constants']['included_node_types'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $query->join('node', 'n', 'n.nid = fg.entity_id');

    // Include certain node types.
    if (!empty($this->includedNodeTypes)) {
      $query->condition('n.type', $this->includedNodeTypes, 'IN');
    }

    return $query;
  }

}
