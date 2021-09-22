<?php

namespace Drupal\eic_media_statistics\Hooks;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount
   */
  protected $entityFileDownloadCount;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The Entity file download count service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(EntityFileDownloadCount $entity_file_download_count, CacheBackendInterface $cache_backend) {
    $this->entityFileDownloadCount = $entity_file_download_count;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_media_statistics.entity_file_download_count'),
      $container->get('cache.default')
    );
  }

  /**
   * Acts on hook_node_view() for node entities.
   *
   * @param array $build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $build['file_download_count'] = [
      '#markup' => '',
      '#value' => $this->entityFileDownloadCount->getFileDownloads($entity),
    ];

    // Add updated entity cache tags so the counters will get updated.
    $cache_tags = isset($build['#cache']['tags']) ? $build['#cache']['tags'] : [];
    $cache_tags = array_unique(array_merge($cache_tags, $entity->getCacheTags()));
    $build['#cache']['tags'] = $cache_tags;
  }

  /**
   * Invalidate cache tags for this entity on update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityUpdate(EntityInterface $entity) {
    if (in_array($entity->getEntityTypeId(), EntityFileDownloadCount::getTrackedEntityTypes())) {
      Cache::invalidateTags($entity->getCacheTagsToInvalidate());
    }
  }

  /**
   * Invalidate cache tags for this entity on delete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityDelete(EntityInterface $entity) {
    if (in_array($entity->getEntityTypeId(), EntityFileDownloadCount::getTrackedEntityTypes())) {
      Cache::invalidateTags($entity->getCacheTagsToInvalidate());
    }
  }

}
