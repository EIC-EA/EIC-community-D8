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
   * @return \Drupal\oec_group_flex\GroupVisibilityRecord|bool
   *   A GroupVisibilityRecord object. FALSE if no matching records were found.
   */
  public function load($id);

  /**
   * Constructs a new GroupVisibilityRecord object, without saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\oec_group_flex\GroupVisibilityRecord
   *   A new GroupVisibilityRecord object.
   */
  public function create(array $values = []);

  /**
   * Deletes permanently saved entities.
   *
   * @param array $entities
   *   An array of entity objects to delete.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function delete(array $entities);

  /**
   * Saves the entity permanently.
   *
   * @param \Drupal\oec_group_flex\GroupVisibilityRecord $group_visibility_record
   *   The group visibility record to save.
   *
   * @return bool
   *   TRUE if the record was saved in the database.
   */
  public function save(GroupVisibilityRecord $group_visibility_record);

}
