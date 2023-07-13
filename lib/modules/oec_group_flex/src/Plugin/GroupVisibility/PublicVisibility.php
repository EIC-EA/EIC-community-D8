<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupVisibility\PublicVisibility as PublicVisibilityBase;

/**
 * Provides a custom plugin that overrides the 'public' group visibility plugin.
 */
class PublicVisibility extends PublicVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $groupType = $group->getGroupType();

    // Add perm when anonymous role has permission to view group on group type.
    $anonymousPermissions = [];
    if ($groupType->getAnonymousRole()->hasPermission('view group')) {
      $anonymousPermissions = [
        $groupType->getAnonymousRoleId() => $this->getGroupPermissionsPerRole($group, $groupType->getAnonymousRole()),
      ];
    }

    // Add view comments permission to outsiders and group members.
    $group_view_permissions[] = 'view comments';

    return array_merge($anonymousPermissions, [
      $groupType->getOutsiderRoleId() => $this->getGroupPermissionsPerRole($group, $groupType->getOutsiderRole()),
      $groupType->getMemberRoleId() => $this->getGroupPermissionsPerRole($group, $groupType->getMemberRole()),
    ]);
  }

  /**
   * Returns the group permissions for the given group and role.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\group\Entity\GroupRoleInterface $role
   *   The role entity.
   *
   * @return string[]
   *   An array of permissions.
   */
  protected function getGroupPermissionsPerRole(GroupInterface $group, GroupRoleInterface $role) {
    $groupType = $group->getGroupType();
    $group_view_permissions[] = 'view group';

    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    foreach ($groupType->getInstalledContentPlugins()->getIterator() as $pluginId => $plugin) {

      switch ($plugin->getPluginDefinition()['id']) {
        case 'group_node':
        case 'group_membership':
        case 'group_content_menu':
          $permission = "view $pluginId entity";

          // Check if this permission should be available for the given group
          // and role.
          $is_allowed = TRUE;
          $context = [
            'plugin' => $plugin,
            'group' => $group,
            'role' => $role,
            'permission' => $permission,
          ];
          \Drupal::moduleHandler()->alter('oec_group_flex_plugin_permission', $is_allowed, $context);

          if ($is_allowed) {
            $group_view_permissions[] = $permission;
          }
          break;

      }
    }
    return $group_view_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Public (The @group_type_name and all its content will be viewed by anonymous users and logged in users)', ['@group_type_name' => strtolower($groupType->label())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name and all its content will be viewed by non-members of the group', ['@group_type_name' => $groupType->label()]);
  }

}
