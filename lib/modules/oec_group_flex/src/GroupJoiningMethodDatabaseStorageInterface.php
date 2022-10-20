<?php

namespace Drupal\oec_group_flex;

/**
 * Provides an interface defining Group Joining Databse Storage.
 *
 * Stores the group joining method in the database.
 */
interface GroupJoiningMethodDatabaseStorageInterface {

  /**
   * Loads a group joining method record from database.
   *
   * @param mixed $gid
   *   The Group entity ID of the visibility record to load.
   *
   * @return \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface|bool
   *   A GroupJoiningMethodRecord object. FALSE if no matching records were
   *   found.
   */
  public function load($gid);

  /**
   * Constructs a new GroupJoiningMethodRecord object, without saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface|FALSE
   *   A new GroupJoiningMethodRecord object.
   */
  public function create(array $values = []);

  /**
   * Saves the group joining method record permanently.
   *
   * @param \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface $group_visibility_record
   *   The group joining method record to save.
   *
   * @return bool
   *   TRUE if the record was saved in the database.
   */
  public function save(GroupJoiningMethodRecordInterface $group_visibility_record);

  /**
   * Deletes permanently saved group joining method records.
   *
   * @param \Drupal\oec_group_flex\GroupJoiningMethodRecordInterface[] $entities
   *   An array of entity objects to delete.
   */
  public function delete(array $entities);

}
