<?php

namespace Drupal\oec_group_flex;

/**
 * Defines a class for oec_group_visibility database records.
 */
class GroupJoiningMethodRecord implements GroupJoiningMethodRecordInterface {

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
   * Constructs a new GroupVisibilityRecord object.
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
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->gid;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupId($gid) {
    $this->gid = $gid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options = []) {
    $this->options = $options;
    return $this;
  }

}
