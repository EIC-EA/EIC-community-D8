<?php

/**
 * @file
 * Helper install functions for the EIC Groups module.
 */

use Drupal\Core\Cache\Cache;
use Drupal\group_permissions\Entity\GroupPermission;

/**
 * Operation batch to modify group permission.
 */
function _eic_groups_batch_modify_group_permissions(array $permissions, array $internal_roles, array $outsider_roles, string $operation, array $query_conditions, int $progress, int $max, int $total, &$context) {
  $context['message'] = t('Modifying group permissions - @progress of @total', [
    '@progress' => $progress,
    '@total' => $total,
  ]);

  // Prepare the query.
  $query = \Drupal::entityQuery('group_permission')
    ->range($progress, $max)
    ->accessCheck(FALSE);
  foreach ($query_conditions as $condition) {
    if (!isset($condition['operator'])) {
      // If operator is not provided, we set it as NULL. The query will do the
      // rest.
      $condition['operator'] = NULL;
    }
    $query->condition($condition['field'], $condition['value'], $condition['operator']);
  }
  $results = $query->execute();

  $group_permissions = GroupPermission::loadMultiple($results);

  foreach ($group_permissions as &$group_permission) {
    // Check that we have a proper operation.
    if (!in_array($operation, ['add', 'remove'])) {
      continue;
    }

    // (Re)initialize the array.
    $role_ids = [];

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $group_permission->getGroup();
    $group_type = $group->getGroupType();

    // Prepare the role IDs.
    foreach ($internal_roles as $role) {
      // Filter out roles that don't belong to the group type.
      if (strpos($role, $group_type->id() . '-') !== 0) {
        continue;
      }
      $role_ids[] = $role;
    }
    /** @var \Drupal\group\GroupRoleSynchronizerInterface $groupRoleSynchronizer */
    $groupRoleSynchronizer = \Drupal::service('group_role.synchronizer');
    foreach ($outsider_roles as $role) {
      $role_ids[] = $groupRoleSynchronizer->getGroupRoleId($group_type->id(), $role);
    }

    foreach ($role_ids as $role_id) {
      switch ($operation) {
        case 'add':
          \Drupal::service('eic_groups.helper')->addRolePermissionsToGroup(
            $group_permission,
            $role_id,
            $permissions
          );
          break;

        case 'remove':
          \Drupal::service('eic_groups.helper')->removeRolePermissionsFromGroup(
            $group_permission,
            $role_id,
            $permissions
          );
          break;
      }
    }

    $group_permission->setNewRevision();
    $group_permission->setRevisionUserId(1);
    $group_permission->setRevisionCreationTime(time());
    $group_permission->setRevisionLogMessage("Modified permissions.");

    if (count($group_permission->validate()) > 0) {
      continue;
    }

    $group_permission->save();

    Cache::invalidateTags($group->getCacheTags());
  }
}
