<?php

namespace Drupal\eic_group_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex;
use Drupal\eic_group_statistics\GroupStatisticsStorageInterface;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\group\Entity\GroupContentInterface;
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
   * The Entity Usage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_group_statistics\GroupStatisticsStorageInterface $group_statistics_storage
   *   The Group statistics storage service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex
   *   The Group statistics search API Reindex service.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The Entity Usage service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    GroupStatisticsStorageInterface $group_statistics_storage,
    GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex,
    EntityUsageInterface $entity_usage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStatisticsStorage = $group_statistics_storage;
    $this->groupStatisticsSearchApiReindex = $group_statistics_sear_api_reindex;
    $this->entityUsage = $entity_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_group_statistics.storage'),
      $container->get('eic_group_statistics.search_api.reindex'),
      $container->get('entity_usage.usage')
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
        // Increments number of members in the group statistics.
        $this->groupStatisticsStorage->increment($group, GroupStatisticsStorageInterface::STAT_TYPE_MEMBERS);
        break;

      case 'group-group_node-document':
      case 'group-group_node-wiki_page':
      case 'group-group_node-gallery':
        /** @var \Drupal\node\NodeInterface $node */
        $node = $entity->getEntity();

        $medias = [];

        $media_fields = [
          'field_document_media',
          'field_related_downloads',
          'field_photos',
        ];
        foreach ($media_fields as $field_name) {
          if ($node->hasField($field_name)) {
            foreach ($node->get($field_name)->referencedEntities() as $media) {
              /** @var \Drupal\media\MediaInterface $media */
              $medias[] = $media;
            }
          }
        }

        // If node has no media entities, we don't need to update group file
        // statistics.
        if (empty($medias)) {
          $re_index = FALSE;
          break;
        }

        // Counts the number of times we need to increment the file statistic.
        $count = $this->countGroupFileStatistics($group, $node, $medias);

        if (!$count) {
          $re_index = FALSE;
          break;
        }

        // Update group file statistics.
        $this->groupStatisticsStorage->increment($group, GroupStatisticsStorageInterface::STAT_TYPE_FILES, $count);
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
        // Decrements number of members in the group statistics.
        $this->groupStatisticsStorage->decrement($group, GroupStatisticsStorageInterface::STAT_TYPE_MEMBERS);
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
   * Acts on hook_node_delete() for node entities that belong to a group.
   *
   * We need to implement this hook in order to update some group statistics
   * that could not be updated in during
   * eic_group_statistics_group_content_delete phase because the related node
   * gets deleted first.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  public function groupContentNodeDelete(EntityInterface $entity, GroupContentInterface $group_content) {
    $group = $group_content->getGroup();

    $re_index = TRUE;

    switch ($entity->bundle()) {
      case 'document':
      case 'wiki_page':
      case 'gallery':
        $medias = [];

        $media_fields = [
          'field_document_media',
          'field_related_downloads',
          'field_photos',
        ];
        foreach ($media_fields as $field_name) {
          if ($entity->hasField($field_name)) {
            foreach ($entity->get($field_name)->referencedEntities() as $media) {
              /** @var \Drupal\media\MediaInterface $media */
              $medias[] = $media;
            }
          }
        }

        // If node has no media entities, we don't need to update group file
        // statistics.
        if (empty($medias)) {
          $re_index = FALSE;
          break;
        }

        // Counts the number of times we need to decrement the file statistic.
        $count = $this->countGroupFileStatistics($group, $entity, $medias);

        if (!$count) {
          $re_index = FALSE;
          break;
        }

        // Update group file statistics.
        $this->groupStatisticsStorage->decrement($group, GroupStatisticsStorageInterface::STAT_TYPE_FILES, $count);
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
   * Counts group file statistics for a given node with medias.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we want to count file statistics.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity that belongs to the group.
   * @param \Drupal\media\MediaInterface[] $medias
   *   Array of media entities that belong to the node.
   *
   * @return int
   *   The number of times we need to increment/decrement in the group file
   *   statistics.
   */
  private function countGroupFileStatistics(GroupInterface $group, NodeInterface $node, array $medias = []) {
    $count_updates = 0;

    if (!$medias) {
      return $count_updates;
    }

    foreach ($medias as $media) {
      // Loads up the entity usage for the media.
      $media_usage = $this->entityUsage->listSources($media);

      // If there is no media usage, we increment the counter.
      if (!isset($media_usage['node'])) {
        $count_updates++;
        continue;
      }

      // We discard the current node from the usage. We just want to know
      // if the media is referenced in other nodes.
      if (isset($media_usage['node'][$node->id()])) {
        unset($media_usage['node'][$node->id()]);
      }

      // If media is being referenced elsewhere, then we need to make sure it
      // hasn't been referenced in this group before updating the file
      // statistics.
      if (count($media_usage['node']) > 0) {

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

      // At this point, it means the media is not being referenced anywhere
      // which means we can increment the counter.
      $count_updates++;
    }

    return $count_updates;
  }

}
