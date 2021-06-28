<?php

namespace Drupal\eic_groups;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Interface EICGroupsHelperInterface to implement in EICGroupHeader.
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

  /**
   * Get operations links of a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   * @param array $limit_entities
   *   Array of entities types to limit operation links.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   An optional cacheable metadata object.
   *
   * @return array
   *   An associative array of operation links to show when in a group context,
   *   keyed by operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of the operation.
   */
  public function getGroupContentOperationLinks(GroupInterface $group, array $limit_entities = [], CacheableMetadata $cacheable_metadata = NULL);

}
