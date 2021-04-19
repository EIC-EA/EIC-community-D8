<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupVisibilityBase;

/**
 * Provides a 'restricted' group visibility.
 *
 * @GroupVisibility(
 *  id = "restricted",
 *  label = @Translation("Restricted (visible by members and trusted users)"),
 *  weight = -89
 * )
 */
class RestrictedVisibility extends GroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];

    $internal_roles = [
      'trusted_user',
      'content_administrator',
    ];
    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($groupType, $internal_roles);

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $mappedPerm[$outsider_role->id()] = ['view group' => TRUE];
      }
    }

    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];

    $internal_roles = [
      'trusted_user',
      'content_administrator',
    ];
    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($groupType, $internal_roles);

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $mappedPerm[$outsider_role->id()] = ['view group' => FALSE];
      }
    }

    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $permissions = [
      $group->getGroupType()->getMemberRoleId() => ['view group'],
    ];

    $internal_roles = [
      'trusted_user',
      'content_administrator',
    ];
    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($group->getGroupType(), $internal_roles);

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $permissions[$outsider_role->id()] = ['view group'];
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    $permissions = [
      $group->getGroupType()->getAnonymousRoleId() => ['view group'],
      $group->getGroupType()->getOutsiderRoleId() => ['view group'],
    ];

    $internal_roles = [
      'trusted_user',
      'content_administrator',
    ];
    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($group->getGroupType(), $internal_roles);

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $permissions[$outsider_role->id()] = ['view group'];
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Restricted (The @group_type_name will be viewed by group members and trusted users)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name will be viewed by group members and trusted users', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * Get group outsider drupal roles.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type entity.
   * @param array $internal_rids
   *   The outsider role id.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The outsider roles of the group.
   */
  private function getOutsiderRolesFromInteralRoles(GroupTypeInterface $groupType, array $internal_rids): array {
    $roles = [];
    $group_roles = $groupType->getRoles();
    if (!empty($group_roles)) {
      foreach ($group_roles as $role) {
        foreach ($internal_rids as $key => $internal_rid) {
          if ($role->isInternal() && in_array("user.role.{$internal_rid}", $role->getDependencies()['config'])) {
            $roles[] = $role;
            // We unset the role from $internal_rids array to avoid redundant
            // checks.
            unset($internal_rids[$key]);
            break;
          }
        }
      }
    }
    return $roles;
  }

}
