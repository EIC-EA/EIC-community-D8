<?php

namespace Drupal\oec_group_feature;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides an interface defining a group feature entity type.
 */
interface GroupFeatureInterface extends ContentEntityInterface {

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
   *   Features.
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
