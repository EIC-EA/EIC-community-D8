<?php

namespace Drupal\oec_group_flex;

/**
 * Interface for GroupVisibilityRecord objects.
 */
interface GroupVisibilityRecordInterface {

  /**
   * Gets the group visibility record ID.
   *
   * @return int
   *   The group visibility record ID.
   */
  public function getId();

  /**
   * Gets the group entity ID.
   *
   * @return int
   *   The group entity ID.
   */
  public function getGroupId();

  /**
   * Gets group visibility plugin ID.
   *
   * @return string
   *   The group visibility plugin ID.
   */
  public function getType();

  /**
   * Gets group visibility options.
   *
   * @return array
   *   The group visibility options array.
   */
  public function getOptions();

  /**
   * Sets the group visibility record ID.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface
   *   The group visibility record object.
   */
  public function setId($id);

  /**
   * Sets the group entity ID.
   *
   * @param int $gid
   *   The group entity ID.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface
   *   The group visibility record object.
   */
  public function setGroupId($gid);

  /**
   * Sets the group visibility plugin ID.
   *
   * @param string $type
   *   The group visibility plugin ID.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface
   *   The group visibility record object.
   */
  public function setType($type);

  /**
   * Sets the group visibility options.
   *
   * @param array $options
   *   The group visibility options array.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface
   *   The group visibility record object.
   */
  public function setOptions(array $options = []);

}
