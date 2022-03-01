<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an eic_d7_og_group_ref plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: eic_d7_og_group_ref
 *     source: nid
 *     entity_type: node
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_d7_og_group_ref"
 * )
 */
class OgGroupRef extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $gids = [];
    $migrate_connection = Database::getConnection('default', 'migrate');
    // Get the userid, rid and gid from og_users_roles.
    $results = $migrate_connection->select('og_membership', 'ogm')
      ->condition('entity_type', $this->configuration['entity_type'])
      ->condition('etid', $value)
      ->fields('ogm', ['gid'])
      ->execute()
      ->fetchAll();

    if (is_array($results) && count($results) > 0) {
      return reset($results)->gid;
    }
    return $gids;
  }

}
