<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\oec_group_features\GroupFeatureHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 *     group_bundle: bar
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_group_features"
 * )
 */
class GroupFeatures extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Maps the old group features names to the new ones.
   *
   * @var array
   */
  const GROUP_FEATURES_MAPPING = [
    'group' => [
      'c4m_features_og_discussions' => 'eic_groups_discussions',
      'c4m_features_og_documents' => 'eic_groups_files',
      'c4m_features_og_events' => 'eic_groups_group_events',
      'c4m_features_og_highlights' => NULL,
      'c4m_features_og_media' => 'eic_groups_files',
      'c4m_features_og_members' => 'eic_groups_members',
      'c4m_features_og_news' => 'eic_groups_news',
      'c4m_features_og_wiki' => 'eic_groups_wiki',
    ],
    'organisation' => [
      'c4m_features_og_events' => 'eic_groups_anchor_group_events',
      'c4m_features_og_media' => NULL,
      'c4m_features_og_members' => 'eic_groups_anchor_members',
      'c4m_features_og_news' => 'eic_groups_anchor_news',
    ],
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
   * The OEC Group feature helper.
   *
   * @var \Drupal\oec_group_features\GroupFeatureHelper
   */
  protected $groupFeatureHelper;

  /**
   * GroupFeatures constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\oec_group_features\GroupFeatureHelper $group_feature_helper
   *   The entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupFeatureHelper $group_feature_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupFeatureHelper = $group_feature_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('oec_group_features.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'group_bundle' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['group_bundle'])) {
      return NULL;
    }

    $group_bundle = $this->configuration['group_bundle'];

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
          $enabled_features[] = self::GROUP_FEATURES_MAPPING[$group_bundle][$old_feature_name];
        }
      }
    }
    else {
      // If there is no record this means that all features are enabled by
      // default.
      $enabled_features = array_merge($enabled_features, array_values(self::GROUP_FEATURES_MAPPING[$group_bundle]));
    }

    // Filter out feature that are not available for this group type.
    $group_type_allowed_features = array_keys($this->groupFeatureHelper->getGroupTypeAvailableFeatures(
      $this->configuration['group_bundle'])
    );
    foreach ($enabled_features as $index => $enabled_feature) {
      if (!in_array($enabled_feature, $group_type_allowed_features)) {
        unset($enabled_features[$index]);
      }
    }

    // Add features that should be enabled by default and return result.
    return array_merge($enabled_features, self::GROUP_FEATURES_ENABLED_BY_DEFAULT);
  }

}
