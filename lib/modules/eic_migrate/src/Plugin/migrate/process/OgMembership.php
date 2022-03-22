<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an eic_d7_og_membership plugin.
 *
 * This module helps to get the og_membership of an entity.
 * It will return the group ID(s).
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: eic_d7_og_membership
 *     source: nid
 *     entity_type: node
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_d7_og_membership"
 * )
 */
class OgMembership extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migrate_connection = Database::getConnection('default', 'migrate');
    // Get the group IDs for the given value and entity type.
    return $migrate_connection->select('og_membership', 'ogm')
      ->condition('entity_type', $this->configuration['entity_type'])
      ->condition('etid', $value)
      ->fields('ogm', ['gid'])
      ->execute()
      ->fetchField();
  }

}
