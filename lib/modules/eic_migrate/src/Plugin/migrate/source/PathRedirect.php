<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\redirect\Plugin\migrate\source\d7\PathRedirect as PathRedirectBase;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_path_redirect",
 *   source_module = "redirect"
 * )
 */
class PathRedirect extends PathRedirectBase {

  /**
   * The entity type to select.
   *
   * @var string
   */
  protected $entityType;

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

    $this->entityType = !empty($configuration['constants']['entity_type']) ? $configuration['constants']['entity_type'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // Only url redirects from the configured entity type.
    if (!empty($this->entityType)) {
      $query->condition('p.redirect', $this->entityType . '/%', 'LIKE');
    }

    return $query;
  }

}
