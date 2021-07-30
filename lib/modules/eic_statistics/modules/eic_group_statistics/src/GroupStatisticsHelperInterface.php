<?php

namespace Drupal\eic_group_statistics;

use Drupal\group\Entity\GroupInterface;

/**
 * Interface to implement in GroupStatisticsHelper.
 */
interface GroupStatisticsHelperInterface {

  /**
   * Loads a group statistics from database.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\eic_group_statistics\GroupStatistics
   *   The group statistics object.
   */
  public function loadGroupStatistics(GroupInterface $group);

  /**
   * Loads a group statistics from SOLR index.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\eic_group_statistics\GroupStatistics
   *   The group statistics object.
   */
  public function loadGroupStatisticsFromSearchIndex(GroupInterface $group);

  /**
   * Updates group statistics for all groups.
   *
   * This is a very exhaustive process and may take some time to finish
   * depending on the number of groups and group content entities in the DB.
   */
  public function updateAllGroupsStatistics();

}
