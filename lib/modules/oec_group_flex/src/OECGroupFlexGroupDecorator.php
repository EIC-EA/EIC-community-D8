<?php

namespace Drupal\oec_group_flex;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\GroupFlexGroup;
use Drupal\group_flex\GroupFlexGroupType;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Drupal\group_permissions\GroupPermissionsManager;
use Drupal\oec_group_flex\Plugin\OECGroupVisibilityInterface;

/**
 * Get the group flex settings from a group.
 */
class OECGroupFlexGroupDecorator extends GroupFlexGroup {

  /**
   * The flex group inner service.
   *
   * @var \Drupal\group_flex\GroupFlexGroup
   */
  protected $groupFlexGroup;

  /**
   * The OEC module configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $oecGroupFlexConfigSettings;

  /**
   * Constructs a new OECGroupFlexGroupDecorator.
   *
   * @param \Drupal\group_flex\GroupFlexGroup $groupFlexGroup
   *   The flex group inner service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManager $groupPermManager
   *   The group permissions manager.
   * @param \Drupal\group_flex\GroupFlexGroupType $flexGroupType
   *   The group type flex.
   */
  public function __construct(GroupFlexGroup $groupFlexGroup, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, GroupPermissionsManager $groupPermManager, GroupFlexGroupType $flexGroupType) {
    parent::__construct($entityTypeManager, $groupPermManager, $flexGroupType);
    $this->groupFlexGroup = $groupFlexGroup;
    $this->oecGroupFlexConfigSettings = $configFactory->get('oec_group_flex.settings');
  }

  /**
   * Get the group visibility for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to return the default value for.
   *
   * @return string
   *   The group visibility.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupVisibility(GroupInterface $group): string {
    $default_visibility = $this->groupFlexGroup->getGroupVisibility($group);

    // Since group_flex module doesn't save the group visibility in a property
    // of the group, new custom plugins will always be identified as private.
    // With that in mind we need to add extra checks (based on the permissions
    // of each custom plugin) to make sure the right group visibility is
    // picked up. Currently it's working for 'restricted' visibility.
    // @todo In the future the group_flex module should save the group
    // visibility within a property of the group. Also, when the group is saved
    // it doesn't remove the permissions from the previous visibility before
    // updating the group role permissions.
    switch ($default_visibility) {
      case GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE:
        if ($this->isGroupVisibilityRestricted($group)) {
          $default_visibility = OECGroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_RESTRICTED_ROLE;
        }
        break;

    }

    return $default_visibility;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->groupFlexGroup, $method], $args);
  }

  /**
   * Check if group visibility is 'restricted'.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to return the default value for.
   *
   * @return bool
   *   TRUE if the group is restricted based on outsider permissions.
   */
  private function isGroupVisibilityRestricted(GroupInterface $group) {
    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($group->getGroupType(), $this->oecGroupFlexConfigSettings->get('oec_group_visibility_setings.restricted_role.internal_roles'));

    if (empty($outsider_roles)) {
      return FALSE;
    }

    if (!$group->id()) {
      foreach ($outsider_roles as $outsider_role) {
        $groupTypePermissions = $outsider_role->getPermissions();

        if (!in_array('view group', $groupTypePermissions, TRUE)) {
          return FALSE;
        }
      }
    }
    else {
      // If this is an existing group add default based on permissions.
      $groupPermissions = $this->groupPermManager->getCustomPermissions($group);

      foreach ($outsider_roles as $outsider_role) {
        if (!array_key_exists($outsider_role->id(), $groupPermissions) &&
          !in_array('view group', $groupPermissions[$outsider_role->id()], TRUE)) {
          return FALSE;
        }
      }
    }

    return TRUE;
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
