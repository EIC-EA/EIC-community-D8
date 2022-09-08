<?php

namespace Drupal\eic_group_membership\Plugin;

use Drupal\group\Plugin\GroupMembershipPermissionProvider as GroupMembershipPermissionProviderBase;

/**
 * Overrides group permissions for group_membership GroupContent entities.
 */
class GroupMembershipPermissionProvider extends GroupMembershipPermissionProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getRelationUpdatePermission($scope = 'any') {
    if ($scope === 'own') {
      return parent::getRelationUpdatePermission($scope);
    }
    // Provide our custom permission.
    return 'edit memberships';
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationDeletePermission($scope = 'any') {
    // Delete any is handled by the admin permission.
    if ($scope === 'own') {
      return parent::getRelationDeletePermission($scope);
    }
    // Provide our custom permission.
    return 'delete memberships';
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationCreatePermission() {
    // Provide our custom permission.
    return 'add memberships';
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = parent::buildPermissions();

    // Update the labels of the default permissions.
    $permissions[$this->getRelationUpdatePermission('any')]['title'] = 'Edit memberships';
    $permissions[$this->getRelationUpdatePermission('any')]['description'] = 'Allows to edit memberships independently of the "Administer group members" permission';
    $permissions[$this->getRelationDeletePermission('any')]['title'] = 'Delete memberships';
    $permissions[$this->getRelationDeletePermission('any')]['description'] = 'Allows to delete memberships independently of the "Administer group members" permission';
    $permissions[$this->getRelationCreatePermission()]['title'] = 'Add memberships';
    $permissions[$this->getRelationCreatePermission()]['description'] = 'Allows to add memberships independently of the "Administer group members" permission';

    return $permissions;
  }

}
