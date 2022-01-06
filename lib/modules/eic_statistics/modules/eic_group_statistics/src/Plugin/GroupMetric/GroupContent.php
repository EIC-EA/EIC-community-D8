<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group content.
 *
 * @GroupMetric(
 *   id = "eic_groups_content",
 *   label = @Translation("Group content"),
 *   description = @Translation("Provides a counter for group content.")
 * )
 */
class GroupContent extends GroupMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getConfigDefinition(): array {
    return [
      'node_types' => [
        'default_value' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(array $values = []): array {
    // Get the existing node types.
    $node_types = [];
    /** @var \Drupal\node\Entity\NodeType $node_type */
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $node_type) {
      $node_types[$node_type->id()] = $node_type->label();
    }
    return [
      'node_types' => [
        '#title' => $this->t('Select the content type(s) to filter on'),
        '#description' => $this->t('If none selected, all content types will be returned.'),
        '#type' => 'checkboxes',
        '#options' => $node_types,
        '#default_value' => $values['node_types'] ?? [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []): int {
    $selected_node_types = $this->getSelectedOptions($configuration['node_types']);
    $count = 0;
    foreach ($selected_node_types as $node_type) {
      if ($this->groupsHelper->isGroupTypePluginEnabled($group->getGroupType(), 'group_node', $node_type)) {
        $count += count($group->getContentEntities("group_node:$node_type"));
      }
    }
    return $count;
  }

}
