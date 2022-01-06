<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group flags.
 *
 * @GroupMetric(
 *   id = "eic_groups_flags",
 *   label = @Translation("Group flags"),
 *   description = @Translation("Provides a counter for group flags.")
 * )
 */
class GroupFlags extends GroupMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getConfigDefinition(): array {
    return [
      'flags' => [
        'default_value' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(array $values = []): array {
    // Get the existing flags.
    $flags = [];
    /** @var \Drupal\flag\Entity\Flag $flag */
    foreach ($this->entityTypeManager->getStorage('flag')->loadMultiple() as $flag) {
      $flags[$flag->id()] = $flag->label();
    }
    return [
      'flags' => [
        '#title' => $this->t('Select the flag(s) to filter on'),
        '#description' => $this->t('If none selected, all flags will be returned.'),
        '#type' => 'checkboxes',
        '#options' => $flags,
        '#default_value' => $values['flags'] ?? [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []): int {
    $count = 0;
    $selected_flags = $this->getSelectedOptions($configuration['flags']);
    $group_flag_counts = $this->flagHelper->getFlaggingsCountPerGroup($group, TRUE);
    foreach ($selected_flags as $flag_id) {
      foreach ($group_flag_counts as $results) {
        if (!empty($results[$flag_id])) {
          $count += $results[$flag_id];
        }
      }
    }
    return $count;
  }

}
