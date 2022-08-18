<?php

/**
 * @file
 * Hooks provided by the eic_groups module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Responds to group predelete before removing all group content.
 *
 * @param \Drupal\group\Entity\GroupInterface[] $entities
 *   Array of group entities to delete.
 */
function hook_eic_groups_group_predelete(array $entities) {
  // Log message before the deleting all groups.
  foreach ($entities as $group) {
    \Drupal::logger('eic_groups')->notice('Group ' . $group->label() . ' is about to be deleted.');
  }
}

/**
 * Allows to dynamically alter the public availability of a group feature.
 *
 * @param bool $is_publicly_available
 *   Wether the feature should be public.
 * @param array $context
 *   Array containing:
 *   - group: the group entity.
 *   - group_feature: the plugin ID.
 */
function hook_eic_groups_group_feature_public_alter(bool &$is_publicly_available, array $context) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $context['group'];
  $feature_id = $context['group_feature'];
  if ($group->getGroupType()->id() == 'group' && $feature_id == 'private_feature') {
    $is_publicly_available = FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
