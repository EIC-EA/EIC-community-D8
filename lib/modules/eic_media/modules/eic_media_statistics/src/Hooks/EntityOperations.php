<?php

namespace Drupal\eic_media_statistics\Hooks;

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
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The Entity file download count service.
   */
  public function __construct(EntityFileDownloadCount $entity_file_download_count) {
    $this->entityFileDownloadCount = $entity_file_download_count;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_media_statistics.entity_file_download_count')
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
    $build['stat_downloads'] = $this->entityFileDownloadCount->countFileDownloads($entity);
  }

}
