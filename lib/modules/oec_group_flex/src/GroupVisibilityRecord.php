<?php

namespace Drupal\oec_group_flex;

/**
 * Provides the default storage backend for Group visibility plugins.
 */
class GroupVisibilityRecord {

  /**
   * Group visibility record ID.
   *
   * @var int
   */
  public $id;

  /**
   * Group entity ID.
   *
   * @var int
   */
  private $gid;

  /**
   * Group visibility plugin id.
   *
   * @var string
   */
  private $type;

  /**
   * Group visibility plugin options.
   *
   * @var array
   */
  private $options;

  /**
   * Constructs a new GroupVisibility object.
   *
   * @param int $id
   *   The Group visibility record ID.
   * @param int $gid
   *   The group entity ID.
   * @param string $type
   *   The group visibility plugin ID.
   * @param array $options
   *   The group visibility options.
   */
  public function __construct($id, $gid, $type, array $options = []) {
    $this->id = $id;
    $this->gid = $gid;
    $this->type = $type;
    $this->options = $options;
  }

  /**
   * Gets the group visibility item ID.
   *
   * @return int
   *   The group visibility item ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the group entity ID.
   *
   * @return int
   *   The group entity ID.
   */
  public function getGroupId() {
    return $this->gid;
  }

  /**
   * Gets group visibility plugin ID.
   *
   * @return string
   *   The group visibility plugin ID.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Gets group visibility options.
   *
   * @return array
   *   The group visibility options array.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Sets the group visibility item ID.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityItem
   *   The group visibility item object.
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * Sets the group entity ID.
   *
   * @param int $gid
   *   The group entity ID.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityItem
   *   The group visibility item object.
   */
  public function setGroupId($gid) {
    $this->gid = $gid;
    return $this;
  }

  /**
   * Sets the group visibility plugin ID.
   *
   * @param string $type
   *   The group visibility plugin ID.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityItem
   *   The group visibility item object.
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * Sets the group visibility options.
   *
   * @param array $options
   *   The group visibility options array.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityItem
   *   The group visibility item object.
   */
  public function setOptions(array $options = []) {
    $this->options = $options;
    return $this;
  }

}
