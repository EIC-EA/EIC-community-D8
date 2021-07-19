<?php

namespace Drupal\eic_group_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex;
use Drupal\eic_group_statistics\GroupStatisticsStorageInterface;
use Drupal\group\Entity\GroupInterface;
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
   * The Group statistics storage.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsStorageInterface
   */
  protected $groupStatisticsStorage;

  /**
   * The Group statistics search API Reindex service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex
   */
  protected $groupStatisticsSearchApiReindex;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_group_statistics\GroupStatisticsStorageInterface $group_statistics_storage
   *   The Group statistics storage service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex
   *   The Group statistics search API Reindex service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    GroupStatisticsStorageInterface $group_statistics_storage,
    GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStatisticsStorage = $group_statistics_storage;
    $this->groupStatisticsSearchApiReindex = $group_statistics_sear_api_reindex;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_group_statistics.storage'),
      $container->get('eic_group_statistics.search_api.reindex')
    );
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for group_content entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity object.
   */
  public function groupContentInsert(EntityInterface $entity) {
    $group = $entity->getGroup();

    $re_index = TRUE;

    switch ($entity->bundle()) {
      case 'group-group_membership':
        $this->groupStatisticsStorage->increment($group, GroupStatisticsStorageInterface::STAT_TYPE_MEMBERS);
        break;

      case 'group-group_node-document':
        /** @var \Drupal\node\NodeInterface $node */
        $node = $entity->getEntity();

        /** @var \Drupal\media\MediaInterface[] $medias */
        $medias = $node->get('field_document_media')->referencedEntities();

        // Update group file statistics.
        if (!$this->updateGroupFileStatistics($group, $node, $medias)) {
          $re_index = FALSE;
        }
        break;

      default:
        $re_index = FALSE;
        break;

    }

    if (!$re_index) {
      return;
    }

    // Re-index group statistics.
    $this->groupStatisticsSearchApiReindex->reindexItem($group);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for group_content entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity object.
   */
  public function groupContentDelete(EntityInterface $entity) {
    $group = $entity->getGroup();

    $re_index = TRUE;

    switch ($entity->bundle()) {
      case 'group-group_membership':
        $this->groupStatisticsStorage->decrement($group, GroupStatisticsStorageInterface::STAT_TYPE_MEMBERS);
        break;

      case 'group-group_node-document':
        // @todo Decrement group file counter if there are no references.
        break;

      default:
        $re_index = FALSE;
        break;

    }

    if (!$re_index) {
      return;
    }

    // Re-index group statistics.
    $this->groupStatisticsSearchApiReindex->reindexItem($group);
  }

  /**
   * Increments/decrements group file statistics for a given node.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we want to increment file statistics.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity that belongs to the group.
   * @param \Drupal\media\MediaInterface[] $medias
   *   Array of media entities that belong to the node.
   * @param string $update_type
   *   The update type: either "increment" or "decrement".
   *
   * @return bool
   *   TRUE if the counter has been updated.
   */
  private function updateGroupFileStatistics(GroupInterface $group, NodeInterface $node, array $medias = [], $update_type = 'increment') {
    $counter_updated = FALSE;

    if (!in_array($update_type, ['increment', 'decrement'])) {
      return $counter_updated;
    }

    if (!$medias) {
      return $counter_updated;
    }

    foreach ($medias as $media) {
      // @todo Replace \Drupal::service() with proper dependency injection.
      $media_usage = \Drupal::service('entity_usage.usage')->listSources($media);

      // If there is no media usage, we don't update file statistics.
      if (!isset($media_usage['node'])) {
        continue;
      }

      // If media is being referenced elsewhere, then we need to make sure it
      // hasn't been referenced in this group before updating the file
      // statistics.
      if (count($media_usage['node']) > 1) {

        // We discard the current node from the usage. We just want to know
        // if the media is referenced in other nodes.
        unset($media_usage['node'][$node->id()]);

        // Load the source nodes.
        $source_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple(array_keys($media_usage['node']));

        $duplicated_media = FALSE;

        foreach ($source_nodes as $source_node) {
          $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($source_node);
          $group_content = reset($group_contents);

          // If the source node doesn't have any group content associated, we
          // skip this one and check the remaining ones.
          if (!$group_content) {
            continue;
          }

          // If the source node belongs to the group, then we know this media
          // is a duplicated one and we don't need to update the counter.
          if ($group_content->getGroup()->id() === $group->id()) {
            $duplicated_media = TRUE;
            break;
          }
        }

        if ($duplicated_media) {
          continue;
        }
      }

      // At this point, it means the media is not being referenced more than
      // once which means we can update the counter.
      switch ($update_type) {
        case 'increment':
          $this->groupStatisticsStorage->increment($group, GroupStatisticsStorageInterface::STAT_TYPE_FILES);
          break;

        case 'decrement':
          $this->groupStatisticsStorage->decrement($group, GroupStatisticsStorageInterface::STAT_TYPE_FILES);
          break;

      }

      $counter_updated = TRUE;
    }

    return $counter_updated;
  }

}
