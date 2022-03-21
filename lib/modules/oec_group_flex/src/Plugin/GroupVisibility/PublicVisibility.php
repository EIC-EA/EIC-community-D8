<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
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
    $group_view_permissions = ['view group'];

    $installedContentPlugins = $groupType->getInstalledContentPlugins();
    foreach ($installedContentPlugins->getIterator() as $pluginId => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      switch ($plugin->getPluginDefinition()['id']) {
        case 'group_node':
        case 'group_membership':
        case 'group_content_menu':
          $group_view_permissions[] = "view $pluginId entity";
          break;

      }
    }

    // Add perm when anonymous role has permission to view group on group type.
    $anonymousPermissions = [];
    if ($groupType->getAnonymousRole()->hasPermission('view group')) {
      $anonymousPermissions = [$groupType->getAnonymousRoleId() => $group_view_permissions];
    }

    // Add view comments permission to outsiders and group members.
    $group_view_permissions[] = 'view comments';

    return array_merge($anonymousPermissions, [
      $groupType->getOutsiderRoleId() => $group_view_permissions,
      $groupType->getMemberRoleId() => $group_view_permissions,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Public (The @group_type_name and all its content will be viewed by non-members of the group)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name and all its content will be viewed by non-members of the group', ['@group_type_name' => $groupType->label()]);
  }

}
