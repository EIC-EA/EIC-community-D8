<?php

namespace Drupal\eic_groups;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;

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
  public function getOwnerGroupByEntity(EntityInterface $entity);

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

  /**
   * Returns the top-level book page for a given group.
   *
   * This method will always return the first item found.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return int
   *   The book page nid or NULL if not found.
   */
  public function getGroupBookPage(GroupInterface $group);

  /**
   * Add role permissions to a groupPermission object without saving.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permissions
   *   The group permission object.
   * @param string $role
   *   The user role ID.
   * @param array $role_permissions
   *   Associative array of permissions.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The updated group permissions.
   */
  public function addRolePermissionsToGroup(GroupPermissionInterface $group_permissions, string $role, array $role_permissions);

  /**
   * Remove role permissions from a certain group without saving.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permissions
   *   The group permission object.
   * @param string $role
   *   The user role ID.
   * @param array $role_permissions
   *   Associative array of permissions.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The updated group permissions.
   */
  public function removeRolePermissionsFromGroup(GroupPermissionInterface $group_permissions, string $role, array $role_permissions);

  /**
   * Saves a GroupPermission object.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permissions
   *   The GroupPermission object to be saved.
   */
  public function saveGroupPermissions(GroupPermissionInterface $group_permissions);

}
