<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an eic_group_features plugin.
 *
 * This plugin will migrate the group features from D7 to D9.
 *
 * Usage:
 *
 * @code
 * process:
 *   foo:
 *     plugin: eic_group_features
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_group_features"
 * )
 */
class GroupFeatures extends ProcessPluginBase {

  /**
   * Maps the old group features names to the new ones.
   *
   * @var array
   */
  const GROUP_FEATURES_MAPPING = [
    'c4m_features_og_discussions' => 'eic_groups_discussions',
    'c4m_features_og_documents' => 'eic_groups_files',
    'c4m_features_og_events' => 'eic_groups_group_events',
    'c4m_features_og_highlights' => NULL,
    'c4m_features_og_media' => 'eic_groups_files',
    'c4m_features_og_members' => 'eic_groups_members',
    'c4m_features_og_news' => 'eic_groups_news',
    'c4m_features_og_wiki' => 'eic_groups_wiki',
  ];

  /**
   * Group features that we enable by default.
   *
   * @var array
   */
  const GROUP_FEATURES_ENABLED_BY_DEFAULT = [
    'eic_groups_latest_activity_stream',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $nid = $row->getSourceIdValues()['nid'];
    $enabled_features = [];

    $migrate_connection = Database::getConnection('default', 'migrate');
    // Get the group features.
    $d7_query = $migrate_connection->select('variable_store', 'vs');
    $d7_query->fields('vs', ['value']);
    $d7_query->condition('vs.realm', 'og');
    $d7_query->condition('vs.realm_key', "node_$nid");
    $d7_query->condition('vs.name', 'c4m_og_features_group');

    $results = $d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    if (count($results)) {
      $record = reset($results);
      $features = unserialize($record['value']);

      // Add features that are enabled din the D7.
      foreach ($features as $old_feature_name => $enabled) {
        if ($enabled === $old_feature_name) {
          $enabled_features[] = self::GROUP_FEATURES_MAPPING[$old_feature_name];
        }
      }
    }
    else {
      // If there is no record this means that all features are enabled by
      // default.
      $enabled_features = array_merge($enabled_features, array_values(self::GROUP_FEATURES_MAPPING));
    }

    // Add features that should be enabled by default and return result.
    return array_merge($enabled_features, self::GROUP_FEATURES_ENABLED_BY_DEFAULT);
  }

}
