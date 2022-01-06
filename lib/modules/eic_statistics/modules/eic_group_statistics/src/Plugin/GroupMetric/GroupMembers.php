<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group members.
 *
 * @GroupMetric(
 *   id = "eic_groups_group_members",
 *   label = @Translation("Group members"),
 *   description = @Translation("Provides a counter for group members.")
 * )
 */
class GroupMembers extends GroupMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getConfigDefinition(): array {
    return [
      'roles' => [
        'default_value' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(array $values = []): array {
    // Get roles.
    $roles = [];
    foreach ($this->groupsHelper->getGroupRoles() as $info) {
      foreach ($info['roles'] as $role_id => $role_label) {
        $roles[$role_id] = $info['label'] . ' - ' . $role_label;
      }
    }
    return [
      'roles' => [
        '#title' => $this->t('Select the role(s) to filter on'),
        '#description' => $this->t('If none selected, all roles will be returned.'),
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $values['roles'] ?? [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []): int {
    $selected_roles = $this->getSelectedOptions($configuration['roles']);
    $selected_roles = empty($selected_roles) ? NULL : $selected_roles;
    return count($group->getMembers($selected_roles));
  }

}
