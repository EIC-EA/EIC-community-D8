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
 * @} End of "addtogroup hooks".
 */
