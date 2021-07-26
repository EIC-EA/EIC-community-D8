<?php

namespace Drupal\eic_group_statistics;

use Drupal\group\Entity\GroupInterface;

/**
 * Provides an interface defining Group Statistics Storage.
 */
interface GroupStatisticsStorageInterface {

  /**
   * Increments counter for a certain statistic type.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   * @param string $statistic_type
   *   The type of statistic to increment the counter.
   * @param int $count
   *   (optional) The increment number to add to the statistic. Defaults to 1.
   */
  public function increment(GroupInterface $group, $statistic_type, $count = 1);

  /**
   * Decrements counter for a certain statistic type.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   * @param string $statistic_type
   *   The type of statistic to increment the counter.
   * @param int $count
   *   (optional) The increment number to add to the statistic. Defaults to 1.
   */
  public function decrement(GroupInterface $group, $statistic_type, $count = 1);

  /**
   * Delete all statistics for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   */
  public function deleteGroupStatistics(GroupInterface $group);

  /**
   * Loads statistics of a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return \Drupal\eic_group_statistics\GroupStatistics
   *   A GroupStatistics object containing all the group statistics.
   */
  public function load(GroupInterface $group);

  /**
   * Sets group statistics given a GroupStatistics object.
   *
   * @param \Drupal\eic_group_statistics\GroupStatistics $group_statistics
   *   The GroupStatistics object.
   */
  public function setGroupStatistics(GroupStatistics $group_statistics);

  /**
   * Sets group statistics given multiple GroupStatistics objects.
   *
   * @param \Drupal\eic_group_statistics\GroupStatistics[] $groups_statistics
   *   The array of GroupStatistics objects.
   */
  public function setMultipleGroupStatistics(array $groups_statistics);

  /**
   * Calculates all statistics for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return \Drupal\eic_group_statistics\GroupStatistics
   *   A GroupStatistics object containing all the group statistics.
   */
  public function calculateGroupStatistics(GroupInterface $group);

}
