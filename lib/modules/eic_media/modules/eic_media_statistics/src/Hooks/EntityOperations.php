<?php

namespace Drupal\eic_media_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\eic_media_statistics\FileStatisticsDatabaseStorage;
use Drupal\entity_usage\EntityUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity Usage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * The File statistics database storage service.
   *
   * @var \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage
   */
  protected $fileStatisticsDbStorage;

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount
   */
  protected $entityFileDownloadCount;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The Entity Usage service.
   * @param \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage $file_statistics_db_storage
   *   The File statistics database storage service.
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The Entity file download count service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityUsageInterface $entity_usage,
    FileStatisticsDatabaseStorage $file_statistics_db_storage,
    EntityFileDownloadCount $entity_file_download_count
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
    $this->fileStatisticsDbStorage = $file_statistics_db_storage;
    $this->entityFileDownloadCount = $entity_file_download_count;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_usage.usage'),
      $container->get('eic_media_statistics.storage.file'),
      $container->get('eic_media_statistics.entity_file_download_count')
    );
  }

  /**
   * Acts on hook_node_view() for node entities that belong to a group.
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
    $build['stat_downloads'] = $this->entityFileDownloadCount->countFileDownloads($entity);
  }

}
