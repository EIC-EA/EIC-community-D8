<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group_flex\Plugin\GroupFlexPluginCollection;

/**
 * Provides the Custom restricted visibility plugin manager.
 */
class CustomRestrictedVisibilityManager extends DefaultPluginManager {

  /**
   * @var \Drupal\group_flex\Plugin\GroupFlexPluginCollection
   */
  private $plugins;

  /**
   * Constructs a new CustomRestrictedVisibilityManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct('Plugin/CustomRestrictedVisibility', $namespaces, $module_handler,
      'Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityInterface',
      'Drupal\oec_group_flex\Annotation\CustomRestrictedVisibility');

    $this->alterInfo('oec_group_flex_custom_restricted_visibility_info');
    $this->setCacheBackend($cache_backend, 'oec_group_flex_custom_restricted_visibility_plugins');
  }

  /**
   * Get all plugins.
   *
   * @return \Drupal\group_flex\Plugin\GroupFlexPluginCollection
   *   The plugin collection.
   */
  public function getAll(): GroupFlexPluginCollection {
    if (!isset($this->plugins)) {
      $collection = new GroupFlexPluginCollection($this, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($this->getDefinitions() as $plugin_id => $unUsedPluginInfo) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      $this->plugins = $collection->sort();
    }

    return $this->plugins;
  }

  /**
   * Get all plugins as array.
   *
   * @return array
   *   An array of plugin implementation.
   */
  public function getAllAsArray(): array {
    $plugins = [];
    foreach ($this->getAll() as $id => $pluginInstance) {
      $plugins[$id] = $pluginInstance;
    }
    return $plugins;
  }

}
