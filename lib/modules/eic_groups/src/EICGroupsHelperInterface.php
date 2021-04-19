<?php

namespace Drupal\eic_groups;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface EICGroupsHelperInterface.
 */
interface EICGroupsHelperInterface {

  /**
   * Get the group from the current route match.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   */
  public function getGroupFromRoute();

  /**
   * Get the Group of a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   */
  public function getGroupByEntity(EntityInterface $entity);

}
