<?php

namespace Drupal\oec_group_feature;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a group feature entity type.
 */
interface GroupFeatureInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the group feature creation timestamp.
   *
   * @return int
   *   Creation timestamp of the group feature.
   */
  public function getCreatedTime();

  /**
   * Sets the group feature creation timestamp.
   *
   * @param int $timestamp
   *   The group feature creation timestamp.
   *
   * @return \Drupal\oec_group_feature\GroupFeatureInterface
   *   The called group feature entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Group.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The called Group permission entity.
   */
  public function getGroup();

  /**
   * Sets the Group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group.
   */
  public function setGroup(GroupInterface $group);

  /**
   * Gets group features.
   *
   * @return array
   *   Group features.
   */
  public function getFeatures();

  /**
   * Sets the group features.
   *
   * @param array $features
   *   Group features.
   */
  public function setFeatures(array $features);

}
