<?php

namespace Drupal\oec_group_flex;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the default storage backend for Group visibility plugins.
 */
class GroupVisibilityDatabaseStorage implements GroupVisibilityDatabaseStorageInterface {

  use DependencySerializationTrait;

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
    $result = $this->connection->select('oec_group_visibility')
      ->fields('oec_group_visibility', ['id', 'gid', 'type', 'options'])
      ->condition('gid', $gid)
      ->range(0, 1)
      ->execute()->fetchAssoc();

    if (empty($result)) {
      return FALSE;
    }

    return new GroupVisibilityRecord(
      $result['id'],
      $result['gid'],
      $result['type'],
      Json::decode($result['options']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $final_values = [];

    // If the required parameters for GroupVisibilityItem are not setm then we
    // do nothing.
    if (!isset($values['id']) || !isset($values['gid']) || !isset($values['type'])) {
      return FALSE;
    }

    // Re-orders array $values into a final array that will contain the right
    // parameters order when creating the GroupVisibilityItem object.
    foreach ($this->getGroupVisibilityProperties() as $property) {
      if (isset($values[$property])) {
        $final_values[] = $values[$property];
      }
    }

    list($id, $gid, $type, $options) = $final_values;

    if (!is_array($options)) {
      $options = [];
    }

    return new GroupVisibilityRecord($id, $gid, $type, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function save(GroupVisibilityRecord $entity) {
    return (bool) $this->connection
      ->merge('oec_group_visibility')
      ->key('id', $entity->getId())
      ->fields([
        'gid' => $entity->getGroupId(),
        'type' => $entity->getType(),
        'options' => Json::encode($entity->getOptions()),
      ])
      ->execute();
  }

  /**
   * Gets GroupVisibility Record properties.
   *
   * @return array
   *   The array of properties.
   */
  public function getGroupVisibilityProperties() {
    return [
      'id',
      'gid',
      'type',
      'options',
    ];
  }

}
