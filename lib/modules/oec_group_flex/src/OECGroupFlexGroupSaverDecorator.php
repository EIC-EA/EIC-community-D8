<?php

namespace Drupal\oec_group_flex;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroup;
use Drupal\group_flex\GroupFlexGroupSaver;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\GroupPermissionsManager;

/**
 * Saving of a Group to implement the correct group type permissions.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 */
class OECGroupFlexGroupSaverDecorator extends GroupFlexGroupSaver {

  /**
   * The group flex saver service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexSaver;

  /**
   * Constructs a new GroupFlexGroupSaver object.
   *
   * @param \Drupal\group_flex\GroupFlexGroupSaver $groupFlexSaver
   *   The group flex saver inner service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManager $groupPermManager
   *   The group permissions manager.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $visibilityManager
   *   The group visibility manager.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $joiningMethodManager
   *   The group joining method manager.
   * @param \Drupal\group_flex\GroupFlexGroup $groupFlex
   *   The group flex.
   */
  public function __construct(GroupFlexGroupSaver $groupFlexSaver, EntityTypeManagerInterface $entityTypeManager, GroupPermissionsManager $groupPermManager, GroupVisibilityManager $visibilityManager, GroupJoiningMethodManager $joiningMethodManager, GroupFlexGroup $groupFlex) {
    parent::__construct($entityTypeManager, $groupPermManager, $visibilityManager, $joiningMethodManager, $groupFlex);
    $this->groupFlexSaver = $groupFlexSaver;
  }

  /**
   * Save the group visibility.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to save.
   * @param string $groupVisibility
   *   The desired visibility of the group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveGroupVisibility(GroupInterface $group, string $groupVisibility) {
    $groupPermission = $this->getGroupPermissionObject($group);

    if (!$groupPermission) {
      return;
    }

    $visibilityPlugins = $this->getAllGroupVisibility();

    /** @var \Drupal\group_flex\Plugin\GroupVisibilityBase $pluginInstance */
    foreach ($visibilityPlugins as $id => $pluginInstance) {
      // Remove role permissions from group.
      if ($groupVisibility !== $id) {
        foreach ($pluginInstance->getDisallowedGroupPermissions($group) as $role => $rolePermissions) {
          $groupPermission = $this->removeRolePermissionsFromGroup($groupPermission, $role, $rolePermissions);
        }
      }
    }

    if (isset($visibilityPlugins[$groupVisibility])) {
      foreach ($visibilityPlugins[$groupVisibility]->getGroupPermissions($group) as $role => $rolePermissions) {
        $groupPermission = $this->addRolePermissionsToGroup($groupPermission, $role, $rolePermissions);
      }
    }

    $violations = $groupPermission->validate();
    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions are not saved correctly, because:' . $message);
    }
    $groupPermission->save();

    // Save the group entity to reset the cache tags.
    $group->save();
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->groupFlexSaver, $method], $args);
  }

  /**
   * Get the groupPermission object, will create a new one if needed.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to get the group permission object for.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission|null
   *   The (new) group permission object, returns NULL if something went wrong.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function getGroupPermissionObject(GroupInterface $group): ?GroupPermission {
    /** @var \Drupal\group_permissions\Entity\GroupPermission $groupPermission */
    $groupPermission = $this->groupPermManager->getGroupPermission($group);

    if ($groupPermission === NULL) {
      // Create the entity.
      $groupPermission = GroupPermission::create([
        'gid' => $group->id(),
        'permissions' => $this->getDefaultGroupTypePermissions($group->getGroupType()),
        'status' => 1,
      ]);
    }
    else {
      $groupPermission->setNewRevision(TRUE);
    }

    return $groupPermission;
  }

  /**
   * Add role permissions to the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $groupPermission
   *   The group permission object to add the permissions to.
   * @param string $role
   *   The role to add the permissions to.
   * @param array $rolePermissions
   *   The permissions to add to the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission
   *   The group permission object with the updated permissions.
   */
  private function addRolePermissionsToGroup(GroupPermission $groupPermission, string $role, array $rolePermissions): GroupPermission {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (!array_key_exists($role, $permissions) || !in_array($permission, $permissions[$role], TRUE)) {
        $permissions[$role][] = $permission;
      }
    }
    $groupPermission->setPermissions($permissions);
    return $groupPermission;
  }

  /**
   * Remove role permissions from the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $groupPermission
   *   The group permission object to set the permissions to.
   * @param string $role
   *   The role to remove the permissions from.
   * @param array $rolePermissions
   *   The permissions to remove from the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission
   *   The group permission object with the updated permissions.
   */
  private function removeRolePermissionsFromGroup(GroupPermission $groupPermission, string $role, array $rolePermissions): GroupPermission {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (array_key_exists($role, $permissions) || in_array($permission, $permissions[$role], TRUE)) {
        $permissions[$role] = array_diff($permissions[$role], [$permission]);
      }
    }
    $groupPermission->setPermissions($permissions);
    return $groupPermission;
  }

}
