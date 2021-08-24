<?php

namespace Drupal\eic_media_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service that counts file downloads for an entity.
 */
class EntityFileDownloadCount {

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage $file_statistics_db_storage
   *   The File statistics database storage service.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    FileStatisticsDatabaseStorage $file_statistics_db_storage
  ) {
    $this->cacheBackend = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fileStatisticsDbStorage = $file_statistics_db_storage;
  }

  /**
   * Counts the number of file downloads for a given entity.
   *
   * This is a recursive function that will look into fields that may contain
   * files. It mainly looks for media entity references and file fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return array|int
   *   An array containing the download count and the cache tags in recursive
   *   mode or the final download count for the original entity.
   */
  public function countFileDownloads(EntityInterface $entity) {
    $cid = 'file_download_stats:' . $entity->getEntityTypeId() . ':' . $entity->id();

    // Look for the item in cache.
    if ($item = $this->cacheBackend->get($cid)) {
      return $item->data;
    }

    $result = [
      'download_count' => 0,
      'cache_tags' => $entity->getCacheTags(),
    ];

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
              $sub_entity_result = self::countFileDownloads($referenced_entity);
              // Combine the download count and cache tags with the one from the
              // sub entity.
              $result['download_count'] += $sub_entity_result['download_count'];
              $result['cache_tags'] = array_unique(array_merge($result['cache_tags'], $sub_entity_result['cache_tags']));
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
          $result = [
            'download_count' => $file_downloads_count,
            'cache_tags' => $entity->getCacheTags(),
          ];
          // Cache the result.
          $this->cacheBackend->set($cid, $result['download_count'], Cache::PERMANENT, $result['cache_tags']);

          // Return the download count for these files and cache tags for this
          // entity.
          return $result;

      }
    }

    // Cache the result.
    $this->cacheBackend->set($cid, $result['download_count'], Cache::PERMANENT, $result['cache_tags']);

    return $result['download_count'];
  }

}
