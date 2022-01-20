<?php

namespace Drupal\eic_group_statistics;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * GroupMetric plugin manager.
 */
class GroupMetricPluginManager extends DefaultPluginManager {

  /**
   * Constructs GroupFeaturePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/GroupMetric',
      $namespaces,
      $module_handler,
      'Drupal\eic_group_statistics\GroupMetricInterface',
      'Drupal\eic_group_statistics\Annotation\GroupMetric'
    );
    $this->alterInfo('group_metric_info');
    $this->setCacheBackend($cache_backend, 'group_metric_plugins');
  }

}
