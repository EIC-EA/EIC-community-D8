<?php

namespace Drupal\eic_media_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_media_statistics\Controller\MediaFileDownloadController;
use Drupal\eic_media_statistics\Event\DownloadCountUpdate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage $file_statistics_db_storage
   *   The File statistics database storage service.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    EventDispatcherInterface $event_dispatcher,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    FileStatisticsDatabaseStorage $file_statistics_db_storage
  ) {
    $this->cacheBackend = $cache_backend;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fileStatisticsDbStorage = $file_statistics_db_storage;
  }

  /**
   * Returns the entity types that are tracked for file download counts.
   *
   * @return string[]
   *   An array containing the entity types.
   */
  public static function getTrackedEntityTypes() {
    return [
      'media',
      'node',
    ];
  }

  /**
   * Returns the number of file downloads for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return int
   *   The number of downloads for this entity.
   */
  public function getFileDownloads(EntityInterface $entity) {
    $file_download_count = $this->countFileDownloads($entity);

    // Add all the returned cache tags to the entity, to be make the entity
    // cache is invalidated when a child entity has changed.
    $entity->addCacheTags($file_download_count['cache_tags']);

    return $file_download_count['download_count'];
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
   * @return array
   *   An array containing the download count and the cache tags as follows:
   *   - download_count: an integer representing the number of downloads.
   *   - cache_tags: the cache tags for the given entity.
   */
  protected function countFileDownloads(EntityInterface $entity) {
    $cid = 'file_download_stats:' . $entity->getEntityTypeId() . ':' . $entity->id();

    // Look for the item in cache.
    if ($item = $this->cacheBackend->get($cid)) {
      return [
        'download_count' => $item->data,
        'cache_tags' => $item->tags,
      ];
    }

    // Initialise the result array.
    $result = [
      'download_count' => 0,
      'cache_tags' => $entity->getCacheTags(),
    ];

    $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($entity_fields as $field) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      switch ($field->getType()) {
        case 'entity_reference':
        case 'entity_reference_revisions':
          $target_entity_type = $field->getConfig($entity->bundle())->getSetting('target_type');
          if (in_array($target_entity_type, ['media', 'paragraph'])) {
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
        case 'image':
          $file_downloads_count = 0;
          $file_ids = [];
          $result = [
            'download_count' => 0,
            'cache_tags' => [],
          ];
          foreach ($entity->get($field->getName())->getValue() as $file_item) {
            $file_ids[] = $file_item['target_id'];
            $result['cache_tags'] = array_unique(array_merge($result['cache_tags'], MediaFileDownloadController::getMediaFileDownloadCacheTags($file_item['target_id'])));
          }
          // Get statistics results for all files.
          $stat_results = $this->fileStatisticsDbStorage->fetchViews($file_ids);
          foreach ($stat_results as $stat_result) {
            /** @var \Drupal\statistics\StatisticsViewsResult $stat_result */
            $file_downloads_count += $stat_result->getTotalCount();
          }
          $result = [
            'download_count' => $file_downloads_count,
            'cache_tags' => array_unique(array_merge($result['cache_tags'], $entity->getCacheTags())),
          ];
          // Cache the result.
          $this->cacheBackend->set($cid, $result['download_count'], Cache::PERMANENT, $result['cache_tags']);

          // Dispatch an event.
          $event = new DownloadCountUpdate($entity, $result['download_count']);
          $this->eventDispatcher->dispatch($event, DownloadCountUpdate::EVENT_NAME);

          // Return the download count for these files and cache tags for this
          // entity.
          return $result;

      }
    }

    // Cache the result.
    $this->cacheBackend->set($cid, $result['download_count'], Cache::PERMANENT, $result['cache_tags']);

    // Dispatch an event.
    $event = new DownloadCountUpdate($entity, $result['download_count']);
    $this->eventDispatcher->dispatch($event, DownloadCountUpdate::EVENT_NAME);

    return $result;
  }

}
