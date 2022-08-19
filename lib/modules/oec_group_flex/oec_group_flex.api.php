<?php

/**
 * @file
 * Hooks provided by the oec_group_flex module.
 */

use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act after group flex visibility settings being inserted or updated.
 *
 * @param \Drupal\flag\FlaggingInterface $flagging
 *   The flag that has been created and applied to the content.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The content entity the flag has been applied to.
 * @param string $type
 *   The type of the flag (delete, archive, etc.).
 *
 * @see \Drupal\eic_flags\RequestTypes
 * @see \Drupal\eic_flags\RequestStatus
 */
function hook_group_flex_visibility_save(
  GroupInterface $group,
  GroupVisibilityRecordInterface $old_group_visibility,
  GroupVisibilityRecordInterface $new_group_visibility
) {
  // Log when group visibility type was changed.
  if ($old_group_visibility->getType() !== $new_group_visibility->getType()) {
    \Drupal::logger('oec_group_flex')->notice('Group visibility has been updated for group - ' . $group->label());
  }
}

/**
 * Allows to deny a permission for a certain role in a certain context.
 *
 * This alter call is being used in Drupal\oec_group_flex\Plugin\GroupVisibility\PublicVisibility
 * only for now.
 *
 * @param bool $is_allowed
 *   Wether to allow the permission.
 * @param array $context
 *   Array containing:
 *   - plugin: the plugin instance.
 *   - group: the group entity.
 *   - role: role being treated.
 *   - permission: the permission being applied.
 */
function hook_oec_group_flex_plugin_permission_alter(bool &$is_allowed, array $context) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $context['group'];
  /** @var \Drupal\group\Entity\GroupRoleInterface $role */
  $role = $context['role'];
  $plugin = $context['plugin'];

  switch ($group->getGroupType()->id()) {
    case 'event':
      switch ($plugin->getPluginDefinition()['id']) {
        case 'group_node':
          // We deny access to view content for anonymous/outsider roles.
          if ($role->isOutsider() || $role->isAnonymous()) {
            $is_allowed = FALSE;
          }
          break;

      }
      break;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
