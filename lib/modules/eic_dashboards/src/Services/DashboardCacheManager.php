<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Cache\CacheBackendInterface;

class DashboardCacheManager {

  public function __construct(
    protected CacheBackendInterface $cacheBackend,
    protected DashboardCumulativeService $dashboardCumulative,
  ) {}

  public function invalidateAllCaches() {
    $dashboard_types = $this->dashboardCumulative->getAllDashboardTypes();
    foreach ($dashboard_types as $dashboard_type) {
      $this->invalidateCacheDashboard($dashboard_type);
    }

  }

  public function invalidateCacheDashboard(string $dashboard_type) {
    $this->cacheBackend->invalidate("dashboard:$dashboard_type");
  }

}
