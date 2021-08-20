<?php

namespace Drupal\eic_media_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_media_statistics\FileStatisticsDatabaseStorage;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Group statistics storage.
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
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The Entity Usage service.
   * @param \Drupal\eic_media_statistics\FileStatisticsDatabaseStorage $file_statistics_db_storage
   *   The File statistics database storage service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityUsageInterface $entity_usage,
    FileStatisticsDatabaseStorage $file_statistics_db_storage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
    $this->fileStatisticsDbStorage = $file_statistics_db_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_usage.usage'),
      $container->get('eic_media_statistics.storage.file')
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
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  public function groupContentNodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode, GroupContentInterface $group_content) {
    switch ($entity->bundle()) {
      case 'document':
      case 'gallery':
        if ($view_mode === 'teaser') {
          $build['stat_downloads'] = $this->countFileDownloads($entity, $group_content);
        }
        break;

    }

  }

  /**
   * Counts the number of file downloads of a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity that belongs to the group.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  private function countFileDownloads(NodeInterface $node, GroupContentInterface $group_content) {
    $file_ids = [];
    $medias = [];
    $downloads_count = 0;

    switch ($node->bundle()) {
      case 'document':
        $medias = $node->get('field_document_media')->referencedEntities();
        break;

      case 'gallery':
        $paragraphs = $node->get('field_gallery_slides')->referencedEntities();

        foreach ($paragraphs as $paragraph) {
          $medias[] = $paragraph->get('field_gallery_slide_media')->entity;
        }
        break;

    }

    foreach ($medias as $media) {
      if ($media->hasField('field_media_video_file')) {
        $file_id = $media->get('field_media_video_file')->target_id;
      }
      if ($media->hasField('field_media_file')) {
        $file_id = $media->get('field_media_file')->target_id;
      }
      if ($media->hasField('oe_media_image')) {
        $file_id = $media->get('oe_media_image')->target_id;
      }

      if (isset($file_id)) {
        $file_ids[] = $file_id;
      }
    }

    if (empty($file_ids)) {
      $build['stat_downloads'] = $downloads_count;
      return;
    }

    $stats = $this->fileStatisticsDbStorage->fetchViews($file_ids);

    foreach ($stats as $stat) {
      $downloads_count += $stat->getTotalCount();
    }
  }

}
