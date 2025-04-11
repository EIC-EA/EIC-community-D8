<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\group\Entity\GroupInterface;

/**
 * Defines community statistics interface.
 */
interface GroupStatisticsInterface {
  /**
   * Returns number of groups of given type.
   */
  public function getNumberOfGroups($groupType);

  /**
   * Returns number of groups of given type
   * created in the past days.
   */
  public function getNumberOfGroupsPastDays($groupType, $days);
}
