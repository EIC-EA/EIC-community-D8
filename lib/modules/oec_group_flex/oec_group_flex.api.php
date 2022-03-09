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
 * @} End of "addtogroup hooks".
 */
