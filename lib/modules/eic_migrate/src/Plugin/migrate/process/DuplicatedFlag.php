<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an eic_d7_duplicated_flag plugin.
 *
 * This module helps to check if a flag already exists.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: eic_d7_duplicated_flag
 *     source:
 *       - entity_id
 *       - uid
 *     flag_id: follow_content
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_d7_duplicated_flag"
 * )
 */
class DuplicatedFlag extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (
      empty($value) ||
      empty($this->configuration['flag_id'])
    ) {
      return FALSE;
    }

    if (!is_array($value)) {
      return FALSE;
    }

    $properties = [
      'flag_id' => $this->configuration['flag_id'],
      'entity_id' => $value[0],
    ];

    if (isset($value[1])) {
      $properties['uid'] = $value[1];
    }

    $flagging_storage = \Drupal::entityTypeManager()->getStorage('flagging');
    $existing_flags = $flagging_storage->loadByProperties($properties);

    return !empty($existing_flags);
  }

}
