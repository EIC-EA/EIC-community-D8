<?php

namespace Drupal\oec_group_flex;

/**
 * Provides an interface defining Group Visibility Database Storage.
 *
 * Stores the group visibility in the database.
 */
interface GroupVisibilityDatabaseStorageInterface {

  /**
   * Loads a group visibility record from database.
   *
   * @param mixed $gid
   *   The Group entity ID of the visibility record to load.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface|bool
   *   A GroupVisibilityRecord object. FALSE if no matching records were found.
   */
  public function load($gid);

  /**
   * Constructs a new GroupVisibilityRecord object, without saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecordInterface|FALSE
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

  /**
   * Loads all group IDs of a certain visibility type.
   *
   * @param string $visibility_type
   *
   * @return array|false
   *   Array of group IDs or FALSE if there are none found.
   */
  public function loadByType(string $visibility_type);

}
