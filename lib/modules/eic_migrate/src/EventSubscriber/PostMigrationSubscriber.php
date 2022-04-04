<?php

namespace Drupal\eic_migrate\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateLookup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Perform post migration tasks.
 *
 * @package Drupal\eic_migrate
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The database connection to the migrate DB.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookup
   */
  protected $migrateLookup;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MessageCreatorBase object.
   *
   * @param \Drupal\migrate\MigrateLookup $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MigrateLookup $migrate_lookup, EntityTypeManagerInterface $entity_type_manager) {
    $connection = Database::getConnection('default', 'migrate');

    $this->connection = $connection;
    $this->migrateLookup = $migrate_lookup;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    return $events;
  }

  /**
   * Run tasks on post migration event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    switch ($event->getMigration()->getBaseId()) {
      case 'upgrade_d7_node_complete_group':
        $this->completeRelatedGroups($event);
        break;

      case 'upgrade_d7_node_complete_article':
        $this->completeRelatedStories($event);
        break;

    }
  }

  /**
   * Sets the correct entity references for field_related_groups field.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  protected function completeRelatedGroups(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Get the original nodes for which we have related groups.
    $d7_query = $this->connection->select('field_data_c4m_related_group', 'rg');
    $d7_query->innerJoin('node', 'n', 'n.nid = rg.c4m_related_group_target_id');
    $d7_query->fields('n', ['nid', 'vid', 'language']);
    $d7_query->fields('rg', ['c4m_related_group_target_id', 'entity_id']);
    $d7_query->addField('rg', 'revision_id', 'parent_vid');
    $d7_query->addField('rg', 'language', 'parent_language');
    $d7_query->condition('rg.entity_type', 'node')
      ->condition('rg.bundle', 'group');

    // Gather all items for each row.
    // Since we are using the node_complete migration, we assume that source ID
    // is always:
    // - destid1: nid.
    // - destid2: vid.
    // - destid3: langcode.
    $node_values = [];
    foreach ($d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $result) {
      $source_ids = [$result['nid'], $result['vid'], $result['language']];
      $parent_source_ids = [
        $result['entity_id'],
        $result['parent_vid'],
        $result['parent_language'],
      ];
      $migrated_row = $migration->getIdMap()->getRowBySource($source_ids);
      $parent_migrated_row = $migration->getIdMap()->getRowBySource($parent_source_ids);
      // Populate the node values.
      $node_values[$parent_migrated_row['destid1']][] = $migrated_row['destid1'];
    }

    // Set the values for each row.
    foreach ($node_values as $parent_id => $items) {
      if ($group = $this->entityTypeManager->getStorage('group')->load($parent_id)) {
        $this->updateEntityReferenceValue($group, 'field_related_groups', $items);
      }
    }
  }

  /**
   * Sets the correct entity references for field_related_stories field.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  protected function completeRelatedStories(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Get the original nodes for which we have related stories.
    $d7_query = $this->connection->select('field_data_c4m_related_articles', 'ra');
    $d7_query->innerJoin('node', 'n', 'n.nid = ra.c4m_related_articles_target_id');
    $d7_query->fields('n', ['nid', 'vid', 'language']);
    $d7_query->fields('ra', ['c4m_related_articles_target_id', 'entity_id']);
    $d7_query->addField('ra', 'revision_id', 'parent_vid');
    $d7_query->addField('ra', 'language', 'parent_language');
    $d7_query->condition('ra.entity_type', 'node')
      ->condition('ra.bundle', 'article');

    // Gather all items for each row.
    // Since we are using the node_complete migration, we assume that source ID
    // is always:
    // - destid1: nid.
    // - destid2: vid.
    // - destid3: langcode.
    $node_values = [];
    foreach ($d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $result) {
      $source_ids = [$result['nid'], $result['vid'], $result['language']];
      $parent_source_ids = [
        $result['entity_id'],
        $result['parent_vid'],
        $result['parent_language'],
      ];
      $migrated_row = $migration->getIdMap()->getRowBySource($source_ids);
      $parent_migrated_row = $migration->getIdMap()->getRowBySource($parent_source_ids);
      // Populate the node values.
      $node_values[$parent_migrated_row['destid1']][] = $migrated_row['destid1'];
    }

    // Set the values for each row.
    foreach ($node_values as $parent_nid => $items) {
      if ($node = $this->entityTypeManager->getStorage('node')->load($parent_nid)) {
        $this->updateEntityReferenceValue($node, 'field_related_stories', $items);
      }
    }
  }

  /**
   * Updates an entity's entity references field with passed values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The source id values of the migrated item.
   * @param string $field_name
   *   The field name to update.
   * @param array $values
   *   The values to be set.
   * @param bool $preserve_existing_values
   *   If FALSE, all pre-existing values will be removed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateEntityReferenceValue(ContentEntityInterface $entity, string $field_name, array $values, bool $preserve_existing_values = TRUE) {
    if (!$preserve_existing_values) {
      $entity->set($field_name, []);
    }

    $changed = $entity->getChangedTime();
    $entity->set($field_name, array_unique(array_merge($entity->get($field_name)->getValue(), $values)));
    $entity->setNewRevision(FALSE);
    $entity->save();

    // Preserve the changed timestamp.
    $entity->setChangedTime($changed);
    $entity->save();
  }

}
