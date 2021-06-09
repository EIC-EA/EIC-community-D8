<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface RequestManagerInterface
 *
 * @package Drupal\eic_flags
 */
interface RequestManagerInterface {

  /**
   * Applies the given the corresponding flag to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $reason
   *
   * @return mixed
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason);

}
