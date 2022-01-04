<?php

namespace Drupal\eic_groups\Plugin\GroupMetric;

use Drupal\eic_groups\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group downloads.
 *
 * @GroupMetric(
 *   id = "eic_groups_downloads",
 *   label = @Translation("Group downloads"),
 *   description = @Translation("Provides a counter for group downloads.")
 * )
 */
class GroupDownloads extends GroupMetricPluginBase {

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
    $count = 0;
    foreach ($this->groupsHelper->getGroupNodes($group) as $node) {
      $count += $this->entityFileDownloadCount->getFileDownloads($node);
    }
    return $count;
  }

}
