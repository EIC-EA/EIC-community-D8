<?php

namespace Drupal\oec_group_flex;

/**
 * Provides an interface defining Group Visibility Databse Storage.
 *
 * Stores the group visibility in the database.
 */
interface GroupVisibilityDatabaseStorageInterface {

  /**
   * Loads a group visibility record from database.
   *
   * @param mixed $id
   *   The ID of the group visibility record to load.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface|bool
   *   A GroupVisibilityRecord object. FALSE if no matching records were found.
   */
  public function load($id);

  /**
   * Constructs a new GroupVisibilityRecord object, without saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface
   *   A new GroupVisibilityRecord object.
   */
  public function create(array $values = []);

  /**
   * Saves the group visibility record permanently.
   *
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface $group_visibility_record
   *   The group visibility record to save.
   *
   * @return bool
   *   TRUE if the record was saved in the database.
   */
  public function save(GroupVisibilityRecordInterface $group_visibility_record);

  /**
   * Deletes permanently saved group visibility records.
   *
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface[] $entities
   *   An array of entity objects to delete.
   */
  public function delete(array $entities);

}
