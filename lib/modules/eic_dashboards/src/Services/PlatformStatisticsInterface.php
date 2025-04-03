<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines platform statistics interface.
 */
interface PlatformStatisticsInterface {

  /**
   * Returns total number of platform members.
   */
  public function getTotalPlatformMembers();

}
