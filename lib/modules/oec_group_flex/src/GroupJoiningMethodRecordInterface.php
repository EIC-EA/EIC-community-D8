<?php

namespace Drupal\oec_group_flex;

/**
 * Interface for GroupJoiningMethodRecord objects.
 */
interface GroupJoiningMethodRecordInterface {

  /**
   * Gets the group joining method record ID.
   *
   * @return int
   *   The group joining method record ID.
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
   * Gets group joining method plugin ID.
   *
   * @return string
   *   The group joining method plugin ID.
   */
  public function getType();

  /**
   * Gets group joining method options.
   *
   * @return array
   *   The group joining method options array.
   */
  public function getOptions();

  /**
   * Sets the group joining method record ID.
   *
   * @return \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface
   *   The group joining method record object.
   */
  public function setId($id);

  /**
   * Sets the group entity ID.
   *
   * @param int $gid
   *   The group entity ID.
   *
   * @return \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface
   *   The group joining method record object.
   */
  public function setGroupId($gid);

  /**
   * Sets the group joining method plugin ID.
   *
   * @param string $type
   *   The group joining method plugin ID.
   *
   * @return \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface
   *   The group joining method record object.
   */
  public function setType($type);

  /**
   * Sets the group joining method options.
   *
   * @param array $options
   *   The group joining method options array.
   *
   * @return \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface
   *   The group joining method record object.
   */
  public function setOptions(array $options = []);

}
