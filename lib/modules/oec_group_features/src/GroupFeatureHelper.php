<?php

namespace Drupal\oec_group_features;

use Drupal\Core\Config\ConfigFactory;

/**
 * GroupFeatureHelper service that provides helper functions for Group features.
 */
class GroupFeatureHelper {

  /**
   * Name of the Features field.
   *
   * @var string
   */
  const FEATURES_FIELD_NAME = 'features';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeaturePluginManager;

  /**
   * Constructs a new GroupFeatureHelper.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory service.
   * @param \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_plugin_manager
   *   The configuration factory service.
   */
  public function __construct(
    ConfigFactory $config_factory,
    GroupFeaturePluginManager $group_feature_plugin_manager
  ) {
    $this->configFactory = $config_factory;
    $this->groupFeaturePluginManager = $group_feature_plugin_manager;
  }

  /**
   * Returns all the defined group features.
   *
   * @return array
   *   An array of plugin_id / label.
   */
  public function getAllAvailableFeatures() {
    $available_features = [];
    foreach ($this->groupFeaturePluginManager->getDefinitions() as $definition) {
      $available_features[$definition['id']] = $definition['label'];
    }
    return $available_features;
  }

  /**
   * Returns the available features for a specific group type.
   *
   * @param string $group_type
   *   The group type.
   *
   * @return array
   *   An array of plugin_id / label.
   */
  public function getGroupTypeAvailableFeatures(string $group_type) {
    $available_features = $this->getAllAvailableFeatures();

    if ($config = $this->configFactory->get('oec_group_features.group_type_features.' . $group_type)) {
      $group_type_features = $config->get('features');

      // If the config has specific features enabled, we loop through all
      // features and remove the other ones.
      if (!empty($group_type_features)) {
        foreach ($available_features as $plugin_id => $label) {
          if (!in_array($plugin_id, $group_type_features)) {
            unset($available_features[$plugin_id]);
          }
        }
      }
    }

    return $available_features;
  }

}
