<?php

namespace Drupal\eic_dashboards\Controller;

trait DashboardCacheTrait {

  /**
   * Return the cache IDs per dashboard.
   *
   * @return string[]
   */
  abstract function getDashboardCacheIds(): array;

}
