<?php

namespace Drupal\eic_groups\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\group\Entity\Group as GroupBase;

/**
 * Overrides the Group entity.
 *
 * @ingroup group
 */
class Group extends GroupBase {

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // Invoke custom hook to run before delete all the group content.
    \Drupal::moduleHandler()->invokeAll('eic_groups_group_predelete', [$entities]);
    parent::preDelete($storage, $entities);
  }

}
