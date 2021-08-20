<?php

namespace Drupal\eic_media_statistics;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service that counts file downloads for an entity.
 */
class EntityFileDownloadCount {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The File statistics database storage service.
   *
   * @var \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage
   */
  protected $fileStatisticsDbStorage;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage $file_statistics_db_storage
   *   The File statistics database storage service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    FileStatisticsDatabaseStorage $file_statistics_db_storage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fileStatisticsDbStorage = $file_statistics_db_storage;
  }

  /**
   * Counts the number of file downloads for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function countFileDownloads(EntityInterface $entity) {
    $downloads_count = 0;

    $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($entity_fields as $field) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      switch ($field->getType()) {
        // @todo Handle paragraphs.
        case 'entity_reference':
          $target_entity_type = $field->getConfig($entity->bundle())->getSetting('target_type');
          if ($target_entity_type == 'media') {
            // Load the entities.
            foreach ($entity->get($field->getName())->referencedEntities() as $referenced_entity) {
              $downloads_count += self::countFileDownloads($referenced_entity);
            }
          }
          break;

        case 'file':
          $file_downloads_count = 0;
          $file_ids = [];
          foreach ($entity->get($field->getName())->getValue() as $file_item) {
            $file_ids[] = $file_item['target_id'];
          }
          // Get statistics results for all files.
          $stat_results = $this->fileStatisticsDbStorage->fetchViews($file_ids);
          foreach ($stat_results as $stat_result) {
            /** @var \Drupal\statistics\StatisticsViewsResult $stat_result */
            $file_downloads_count += $stat_result->getTotalCount();
          }
          return $file_downloads_count;

      }
    }

    return $downloads_count;
  }

}
