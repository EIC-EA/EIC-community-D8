<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\group\Entity\GroupInterface;

/**
 * Defines community statistics interface.
 */
interface CommunityStatisticsInterface {

  /**
   * Returns community members who joined between specified dates.
   */
  public function getCommunityMembersJoinedInGivenPeriod(GroupInterface $group, $start_date, $end_date);

  /**
   * Returns community content of bundle created between dates.
   */
  public function getCommunityContentOfBundleInGivenPeriod($bundle, GroupInterface $group, $start_date, $end_date);


}
