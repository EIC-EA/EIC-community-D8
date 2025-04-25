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

  /**
   * Returns groups grouped by status.
   */
  public function getGroupsByStatus($groupType);

  /**
   * Returns groups grouped by visibility.
   */
  public function getGroupsByVisibility($groupType);

  /**
   * Returns top groups by number of members.
   */
  public function getTopGroupsByMembers($membershipType);

  /**
   * Returns top groups by number of given content type.
   */
  public function getTopGroupsByContentType($contentType, $range);

  /**
   * Returns top groups by number of flag count.
   */
  public function getTopGroupsByFlag($groupType, $flagID, $range);

  /**
   * Returns number of groups per taxonomy term.
   */
  public function getGroupsByTerm($groupType, $taxonomyField);
}
