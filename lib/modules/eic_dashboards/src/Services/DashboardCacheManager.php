<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\State\StateInterface;

class DashboardCacheManager {

  public function __construct(
    protected CacheBackendInterface $cacheBackend,
    protected DashboardCumulativeService $dashboardCumulative,
    protected StateInterface $state,
    protected TimeInterface $time,
  ) {}

  public function invalidateAllCaches() {
    $dashboard_types = $this->dashboardCumulative->getAllDashboardTypes();
    foreach ($dashboard_types as $dashboard_type) {
      $this->invalidateCacheDashboard($dashboard_type);
    }
    $this->state->set('dashboards.last_invalidated_cache', $this->time->getRequestTime());
  }

  public function invalidateCacheDashboard(string $dashboard_type) {
    $this->cacheBackend->invalidate("dashboard:$dashboard_type");
  }

}
