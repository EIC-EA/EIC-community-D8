<?php

namespace Drupal\eic_content;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for EICContentHelper that provides helper functions for content.
 */
interface EICContentHelperInterface {

  /**
   * Get all GroupContent entities that represent a given entity.
   *
   * Returns FALSE when group_content module is not enabled.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   * @param array $filter_group_types
   *   Array of group types to be filtered by.
   * @param array $filter_group_content_types
   *   Array of group content types to be filtered by.
   *
   * @return bool|\Drupal\group\Entity\GroupContentInterface[]
   *   A list of GroupContent entities which refer to the given entity.
   */
  public function getGroupContentByEntity(
    ContentEntityInterface $entity,
    array $filter_group_types = [],
    array $filter_group_content_types = []
  );

}
