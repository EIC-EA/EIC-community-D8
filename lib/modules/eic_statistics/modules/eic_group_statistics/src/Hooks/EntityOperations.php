<?php

namespace Drupal\eic_group_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex;
use Drupal\eic_group_statistics\GroupStatisticsStorageInterface;
use Drupal\eic_group_statistics\GroupStatisticTypes;
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
   * The EIC Comments helper service.
   *
   * @var \Drupal\eic_comments\CommentsHelper
   */
  protected $commentsHelper;

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
   * @param \Drupal\eic_comments\CommentsHelper $comments_helper
   *   The EIC Comments helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    GroupStatisticsStorageInterface $group_statistics_storage,
    GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex,
    EntityUsageInterface $entity_usage,
    CommentsHelper $comments_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStatisticsStorage = $group_statistics_storage;
    $this->groupStatisticsSearchApiReindex = $group_statistics_sear_api_reindex;
    $this->entityUsage = $entity_usage;
    $this->commentsHelper = $comments_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_group_statistics.storage'),
      $container->get('eic_group_statistics.search_api.reindex'),
      $container->get('entity_usage.usage'),
      $container->get('eic_comments.helper')
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
        $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_MEMBERS);
        break;

      case 'group-group_node-discussion':
      case 'group-group_node-document':
      case 'group-group_node-wiki_page':
      case 'group-group_node-gallery':
        /** @var \Drupal\node\NodeInterface $node */
        $node = $entity->getEntity();

        // If node is not published, we don't need to update group statistics.
        if (!$node->isPublished()) {
          $re_index = FALSE;
          break;
        }

        $medias = [];

        // @todo In the future we should consider configuring which fields to
        // use via config entity and using an administration form.
        $media_fields = [
          'field_document_media',
          'field_photos',
          'field_related_downloads',
          'field_related_documents',
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
        $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_FILES, $count);
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
        $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_MEMBERS);
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
      case 'discussion':
      case 'document':
      case 'wiki_page':
      case 'gallery':
        $medias = [];

        // @todo In the future we should consider configuring which fields to
        // use via config entity and using an administration form.
        $media_fields = [
          'field_document_media',
          'field_photos',
          'field_related_downloads',
          'field_related_documents',
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
        $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_FILES, $count);
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
   * Acts on hook_node_update() for node entities that belong to a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  public function groupContentNodeUpdate(EntityInterface $entity, GroupContentInterface $group_content) {
    $group = $group_content->getGroup();

    $re_index = TRUE;

    switch ($entity->bundle()) {
      case 'discussion':
      case 'document':
      case 'wiki_page':
      case 'gallery':
        $old_medias = [];
        $medias = [];
        $unpublished_node_medias = [];

        // @todo In the future we should consider configuring which fields to
        // use via config entity and using an administration form.
        $media_fields = [
          'field_document_media',
          'field_photos',
          'field_related_downloads',
          'field_related_documents',
        ];
        foreach ($media_fields as $field_name) {
          if ($entity->hasField($field_name)) {

            // Sets array of old medias to decrement from group file
            // statistics.
            foreach ($entity->original->get($field_name)->referencedEntities() as $media) {
              $old_medias[$media->id()] = $media;
            }

            // Sets array of new medias to increment to the group file
            // statistics.
            foreach ($entity->get($field_name)->referencedEntities() as $media) {
              if (!isset($old_medias[$media->id()])) {
                $medias[$media->id()] = $media;
              }
              else {
                unset($old_medias[$media->id()]);

                // If the node has been unpublished, we add the node medias to
                // an array so that they get decremented from file statistics.
                if ($entity->original->isPublished() && !$entity->isPublished()) {
                  $unpublished_node_medias[$media->id()] = $media;
                }
                elseif (!$entity->original->isPublished() && $entity->isPublished()) {
                  // If the node has been published, we add the node medias to
                  // the medias array so that they get incremented in file
                  // statistics.
                  $medias[$media->id()] = $media;
                }
              }
            }
          }
        }

        // If there are no media entities to increment or decrement, then we
        // don't need to update group file statistics.
        if (empty($medias) && empty($old_medias) && empty($unpublished_node_medias)) {
          $re_index = FALSE;
          break;
        }

        $increment_count = 0;
        if (!empty($medias) && $entity->isPublished()) {
          // Counts the number of times we need to increment to the file
          // statistics.
          $increment_count = $this->countGroupFileStatistics($group, $entity, $medias);
        }

        $decrement_count = 0;
        if (!empty($old_medias) && $entity->original->isPublished()) {
          // Counts the number of times we need to decrement in the file
          // statistics. Note that we decrement only if the previous status
          // was published.
          $decrement_count = $this->countGroupFileStatistics($group, $entity, $old_medias);
        }

        if (!empty($unpublished_node_medias)) {
          // Counts the number of times we need to decrement in the file
          // statistics when the node is unpublished.
          $decrement_count += $this->countGroupFileStatistics($group, $entity, $unpublished_node_medias);
        }

        // If counters are empty, we don't need to update group file
        // statistics.
        if (!$increment_count && !$decrement_count) {
          $re_index = FALSE;
          break;
        }

        // Increments group file statistics.
        if ($increment_count) {
          $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_FILES, $increment_count);
        }

        // Decrements group file statistics.
        if ($decrement_count) {
          $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_FILES, $decrement_count);
        }
        break;

      default:
        $re_index = FALSE;
        break;

    }

    // Increments or decrements group comments statistics.
    if ($entity->hasField('field_comments')) {
      $num_comments = 0;
      // Increments all node comments to the group statistics when node status
      // changes from unpublished to published.
      if (!$entity->original->isPublished() && $entity->isPublished()) {
        $num_comments = $this->commentsHelper->countNodeComments($entity);
        $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_COMMENTS, $num_comments);
        $re_index = TRUE;
      }
      elseif ($entity->original->isPublished() && !$entity->isPublished()) {
        // Decrements all node comments in the group statistics when node status
        // changes from unpublished to published.
        $num_comments = $this->commentsHelper->countNodeComments($entity);
        $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_COMMENTS, $num_comments);
        $re_index = TRUE;
      }

      if ($num_comments > 0) {
        $re_index = TRUE;
      }
    }

    if (!$re_index) {
      return;
    }

    // Re-index group statistics.
    $this->groupStatisticsSearchApiReindex->reindexItem($group);
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
      // Loads up the entity usage for the media. It includes source entity
      // revisions IDs.
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
        $source_nodes = [];

        // Because the media usage is saved per node revision, we need to make
        // sure the media is presented in the latest revision of every node.
        // If the media is not presented in the latest revision of a node, that
        // node will be discarded.
        foreach ($media_usage['node'] as $nid => $media_usage_items) {
          $latest_vid = $this->entityTypeManager->getStorage('node')->getLatestRevisionId($nid);

          foreach ($media_usage_items as $media_usage_item) {
            if ((int) $media_usage_item['source_vid'] === $latest_vid) {
              $node_revision = $this->entityTypeManager->getStorage('node')->loadRevision($latest_vid);

              // Make sure the revision is published, otherwise we skip this
              // node.
              if (!$node_revision->isPublished()) {
                continue;
              }

              $source_nodes[] = $this->entityTypeManager->getStorage('node')->loadRevision($latest_vid);
              // Latest revision has been found in the usage. We don't need
              // go through the remaining items.
              break;
            }
          }
        }

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

  /**
   * Counts the number of file downloads of a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity that belongs to the group.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  private function countFileDownloads(NodeInterface $node, GroupContentInterface $group_content) {
    $file_statistics_storage = \Drupal::service('eic_media_statistics.storage.file');

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

    $stats = $file_statistics_storage->fetchViews($file_ids);

    foreach ($stats as $stat) {
      $downloads_count += $stat->getTotalCount();
    }
  }

}
