<?php

namespace Drupal\oec_group_flex;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the default storage backend for Group Joining plugins.
 */
class GroupJoiningMethodDatabaseStorage implements GroupJoiningMethodDatabaseStorageInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the statistics storage.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for the node view storage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function load($gid) {
    $result = $this->connection->select('oec_group_joining_method')
      ->fields('oec_group_joining_method', ['id', 'gid', 'type', 'options'])
      ->condition('gid', $gid)
      ->range(0, 1)
      ->execute()->fetchAssoc();

    if (empty($result)) {
      return FALSE;
    }

    return new GroupJoiningMethodRecord(
      $result['id'],
      $result['gid'],
      $result['type'],
      Json::decode($result['options']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $final_values = [];

    // If the required parameters for GroupJoiningMethodItem are not set then we
    // do nothing.
    if (!isset($values['id']) || !isset($values['gid']) || !isset($values['type'])) {
      return FALSE;
    }

    // Re-orders array $values into a final array that will contain the right
    // parameters order when creating the GroupJoiningMethodItem object.
    foreach (self::getGroupJoiningMethodRecordProperties() as $property) {
      if (isset($values[$property])) {
        $final_values[] = $values[$property];
      }
    }

    // We don't always have a third element in the array, so test if first.
    if (isset($final_values[3])) {
      list($id, $gid, $type, $options) = $final_values;
    }
    else {
      $options = [];
      list($id, $gid, $type) = $final_values;
    }

    if (!is_array($options)) {
      $options = [];
    }

    return new GroupJoiningMethodRecord($id, $gid, $type, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function save(GroupJoiningMethodRecordInterface $entity) {
    return (bool) $this->connection
      ->merge('oec_group_joining_method')
      ->key('id', $entity->getId())
      ->fields([
        'gid' => $entity->getGroupId(),
        'type' => $entity->getType(),
        'options' => Json::encode($entity->getOptions()),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    $entity_ids = [];

    foreach ($entities as $entity) {
      $entity_ids[] = $entity->getId();
    }

    return (bool) $this->connection
      ->delete('oec_group_joining_method')
      ->condition('id', $entity_ids, 'IN')
      ->execute();
  }

  /**
   * Gets GroupJoiningMethodRecord properties.
   *
   * @return array
   *   The array of properties.
   */
  public static function getGroupJoiningMethodRecordProperties() {
    return [
      'id',
      'gid',
      'type',
      'options',
    ];
  }

}
