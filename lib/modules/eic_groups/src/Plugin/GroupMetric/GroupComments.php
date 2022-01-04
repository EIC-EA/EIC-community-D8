<?php

namespace Drupal\eic_groups\Plugin\GroupMetric;

use Drupal\eic_groups\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group comments.
 *
 * @GroupMetric(
 *   id = "eic_groups_comments",
 *   label = @Translation("Group comments"),
 *   description = @Translation("Provides a counter for group comments.")
 * )
 */
class GroupComments extends GroupMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getConfigDefinition(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(array $values = []): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []): int {
    return $this->groupStatisticsHelper->loadGroupStatistics($group)->getCommentsCount();
  }

}
