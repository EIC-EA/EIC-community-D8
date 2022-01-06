<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group contributors.
 *
 * @GroupMetric(
 *   id = "eic_groups_group_contributors",
 *   label = @Translation("Group contributors"),
 *   description = @Translation("Provides a counter for group contributors.")
 * )
 */
class GroupContributors extends GroupMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []): int {
    $authors = [];
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($this->groupsHelper->getGroupNodes($group) as $node) {
      // Ignore if node is not published.
      if (!$node->isPublished()) {
        continue;
      }

      // Ignore if author is not active.
      if ($node->getOwner()->isActive()) {
        continue;
      }

      if (!isset($authors[$node->getOwnerId()])) {
        $authors[$node->getOwnerId()] = $node->getOwnerId();
      }
    }
    return count($authors);
  }

}
