<?php

namespace Drupal\eic_group_statistics\Hooks;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_group_statistics\GroupStatisticsHelper;
use Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex;
use Drupal\eic_group_statistics\GroupStatisticsStorage;
use Drupal\eic_group_statistics\GroupStatisticsStorageInterface;
use Drupal\eic_group_statistics\GroupStatisticTypes;
use Drupal\eic_search\Service\SolrDocumentProcessor;
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
   * Identifies the file statistics create operation when creating a node.
   */
  const GROUP_FILE_STATISTICS_CREATE_OPERATION = 'create';

  /**
   * Identifies the file statistics update operation when updating a node.
   */
  const GROUP_FILE_STATISTICS_UPDATE_OPERATION = 'update';

  /**
   * Identifies the file statistics delete operation when deleting a node.
   */
  const GROUP_FILE_STATISTICS_DELETE_OPERATION = 'delete';

  /**
   * The Group statistics storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Group statistics helper service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelper
   */
  protected $groupStatisticsHelper;

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
   * The content moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $contentModerationInfo;

  /**
   * The SOLR document processor service.
   *
   * @var SolrDocumentProcessor
   */
  protected $solrDocumentProcessor;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelper $group_statistics_helper
   *   The Group statistics helper service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsStorageInterface $group_statistics_storage
   *   The Group statistics storage service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex
   *   The Group statistics search API Reindex service.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The Entity Usage service.
   * @param \Drupal\eic_comments\CommentsHelper $comments_helper
   *   The EIC Comments helper service.
   * @param \Drupal\content_moderation\ModerationInformation $content_moderation_info
   *   The content moderation information service.
   * @param SolrDocumentProcessor $solr_processor
   *   The SOLR document processor service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    GroupStatisticsHelper $group_statistics_helper,
    GroupStatisticsStorageInterface $group_statistics_storage,
    GroupStatisticsSearchApiReindex $group_statistics_sear_api_reindex,
    EntityUsageInterface $entity_usage,
    CommentsHelper $comments_helper,
    ModerationInformation $content_moderation_info,
    SolrDocumentProcessor $solr_processor
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStatisticsHelper = $group_statistics_helper;
    $this->groupStatisticsStorage = $group_statistics_storage;
    $this->groupStatisticsSearchApiReindex = $group_statistics_sear_api_reindex;
    $this->entityUsage = $entity_usage;
    $this->commentsHelper = $comments_helper;
    $this->solrDocumentProcessor = $solr_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_group_statistics.helper'),
      $container->get('eic_group_statistics.storage'),
      $container->get('eic_group_statistics.search_api.reindex'),
      $container->get('entity_usage.usage'),
      $container->get('eic_comments.helper'),
      $container->get('content_moderation.moderation_information'),
      $container->get('eic_search.solr_document_processor')
    );
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for group_content entities.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $entity
   *   The group content entity object.
   */
  public function groupContentInsert(GroupContentInterface $entity) {
    $group = $entity->getGroup();

    $re_index = TRUE;

    switch ($entity->getContentPlugin()->getPluginId()) {
      case "group_membership":
        $this->solrDocumentProcessor->reIndexEntities([$entity->getGroup(), $entity->getEntity()]);
        // Increments number of members in the group statistics.
        $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_MEMBERS);
        break;

      case "group_node:discussion":
      case "group_node:document":
      case "group_node:event":
      case "group_node:gallery":
      case "group_node:wiki_page":
        if ('group_node:event' === $entity->getContentPlugin()->getPluginId()) {
          $event_count = $this->groupStatisticsStorage->calculateGroupEventsStatistics($group);
          $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_EVENTS, $event_count);
        }

        if (!$this->canUpdateGroupStatistics($entity, self::GROUP_FILE_STATISTICS_CREATE_OPERATION)) {
          return;
        }

        $re_index = $this->updateGroupFileStatistics($entity->getEntity(), $entity);
        // Invalidate cache for group latest activity.
        $this->groupStatisticsHelper->invalidateGroupLatestActivity($group);
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
   * @param \Drupal\group\Entity\GroupContentInterface $entity
   *   The group content entity object.
   */
  public function groupContentDelete(GroupContentInterface $entity) {
    $group = $entity->getGroup();
    $re_index = TRUE;

    switch ($entity->getContentPlugin()->getPluginId()) {
      case "group_membership":
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
   * Acts on hook_entity_pre_delete() for node entities that belong to a group.
   *
   * We need to implement this hook in order to update some group statistics
   * that could not be updated in during
   * eic_group_statistics_group_content_delete phase because the related node
   * gets deleted first.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity object.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  public function groupContentNodePreDelete(NodeInterface $entity, GroupContentInterface $group_content) {
    $group = $group_content->getGroup();

    if (!$this->canUpdateGroupStatistics($entity, self::GROUP_FILE_STATISTICS_DELETE_OPERATION)) {
      return;
    }

    // Invalidate cache for group latest activity.
    $this->groupStatisticsHelper->invalidateGroupLatestActivity($group);

    $re_index = TRUE;

    switch ($entity->bundle()) {
      case 'discussion':
      case 'document':
      case 'event':
      case 'gallery':
      case 'wiki_page':
        $re_index = $this->updateGroupFileStatistics(
          $entity,
          $group_content,
          self::GROUP_FILE_STATISTICS_DELETE_OPERATION
        );
        break;

      default:
        $re_index = FALSE;
        break;

    }

    // Decrements group comments statistics.
    if ($entity->hasField('field_comments') && $entity->isPublished()) {
      // Decrements all node comments from the group statistics.
      $num_comments = $this->commentsHelper->countEntityComments($entity);
      $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_COMMENTS, $num_comments);

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
   * Acts on hook_entity_presave() for node entities that belong to a group.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity object.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   */
  public function groupContentNodePreSave(NodeInterface $entity, GroupContentInterface $group_content) {
    $group = $group_content->getGroup();
    /** @var \Drupal\node\NodeInterface $original_entity */
    $original_entity = $entity->original;
    $re_index = TRUE;

    if (!$this->canUpdateGroupStatistics($entity, self::GROUP_FILE_STATISTICS_UPDATE_OPERATION)) {
      return;
    }

    // Invalidate cache for group latest activity only if node status has
    // changed.
    if ($entity->isPublished() != $original_entity->isPublished()) {
      $this->groupStatisticsHelper->invalidateGroupLatestActivity($group);
    }

    switch ($entity->bundle()) {
      case 'discussion':
      case 'document':
      case 'event':
      case 'gallery':
      case 'wiki_page':
        $re_index = $this->updateGroupFileStatistics(
          $entity,
          $group_content,
          self::GROUP_FILE_STATISTICS_UPDATE_OPERATION
        );
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
      if (!$original_entity->isPublished() && $entity->isPublished()) {
        $num_comments = $this->commentsHelper->countEntityComments($entity);
        $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_COMMENTS, $num_comments);
        $re_index = TRUE;
      }
      elseif ($original_entity->isPublished() && !$entity->isPublished()) {
        // Decrements all node comments in the group statistics when node status
        // changes from unpublished to published.
        $num_comments = $this->commentsHelper->countEntityComments($entity);
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
   * Checks if group statistics can be updated by a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $operation
   *   The entity operation:
   *   - create
   *   - update
   *   - delete.
   *
   * @return bool
   *   TRUE if the entity can update group statistics.
   *
   * @todo We should consider updating the operation constants to a more
   * generic name.
   */
  private function canUpdateGroupStatistics(
    EntityInterface $entity,
    $operation
  ) {
    $can_update = TRUE;

    // If entity is moderated, we need to check the moderation state before
    // update the file statistics.
    if (
      $this->contentModerationInfo instanceof ModerationInformation &&
      $this->contentModerationInfo->isModeratedEntity($entity)
    ) {
      switch ($entity->get('moderation_state')->value) {
        case DefaultContentModerationStates::PUBLISHED_STATE:
          // Entity is published, therefore we can update file statistics.
          break;

        case DefaultContentModerationStates::ARCHIVED_STATE:
          // If entity is archived, we need to make sure there is currently a
          // published version, otherwise we skip file statistics update.
          if (!$this->contentModerationInfo->isDefaultRevisionPublished($entity)) {
            $can_update = FALSE;
          }
          break;

        default:
          // If the entity is being created/updated and there is no published
          // version, we don't update statistics.
          if (
            $operation !== self::GROUP_FILE_STATISTICS_DELETE_OPERATION ||
            !$this->contentModerationInfo->isDefaultRevisionPublished($entity)
          ) {
            $can_update = FALSE;
          }
          break;

      }
    }

    return $can_update;
  }

  /**
   * Updates group file statistics when a node is created/updated/deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity object that relates to the node.
   * @param string $operation
   *   The operation type: "create", "update" or "delete".
   *
   * @return bool
   *   TRUE if the group file statistics have been updated.
   */
  private function updateGroupFileStatistics(
    EntityInterface $entity,
    GroupContentInterface $group_content,
    $operation = self::GROUP_FILE_STATISTICS_CREATE_OPERATION
  ) {
    $group = $group_content->getGroup();

    if (!$this->canUpdateGroupStatistics($entity, $operation)) {
      return FALSE;
    }

    // If entity is moderated, we need to check the moderation state before
    // update the file statistics.
    if (
      $this->contentModerationInfo instanceof ModerationInformation &&
      $this->contentModerationInfo->isModeratedEntity($entity)
    ) {
      switch ($entity->get('moderation_state')->value) {
        case DefaultContentModerationStates::PUBLISHED_STATE:
          // Entity is published, therefore we can update file statistics.
          break;

        case DefaultContentModerationStates::ARCHIVED_STATE:
          // At this point it means we are archiving an entity from a published
          // state, and therefore we force the file statistics delete operation
          // and load the published version in order to decrement statistics.
          $operation = self::GROUP_FILE_STATISTICS_DELETE_OPERATION;
          $entity = $this->entityTypeManager
            ->getStorage($entity->getEntityTypeId())
            ->load($entity->id());
          break;

        default:
          // At this point it means we are deleting a entity with latest
          // revision state different from published or archived, and therefore
          // we load the published version in order to decrement statistics.
          $entity = $this->entityTypeManager
            ->getStorage($entity->getEntityTypeId())
            ->load($entity->id());
          break;

      }
    }

    // If operation is "create" we assume this group content node is new.
    $group_content_is_new = $operation === self::GROUP_FILE_STATISTICS_CREATE_OPERATION ? TRUE : FALSE;

    $old_medias = [];
    $medias = [];
    $unpublished_node_medias = [];

    // Gets the array of field names that will be used to count group file
    // statistics.
    $media_fields = GroupStatisticsStorage::getGroupFileStatisticFields();

    foreach ($media_fields as $key => $field_name) {
      // If $field_name is an array we assume we are dealing with an entity
      // reference field.
      // @todo Currently it supports only paragraph entities. We should improve
      // it in order to support all entity types. This is also not bullet proof
      // since it does not support also more than 1 level of fields and
      // therefore, we can't check for nested entity reference fields.
      if (is_array($field_name)) {
        if ($entity->hasField($key)) {

          /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
          $field_definition = $entity->get($key)->getFieldDefinition();

          if (
            $field_definition->getType() === 'entity_reference_revisions'
          ) {

            // Node is not new, so we need grab the old medias to decrement
            // later.
            if (!$group_content_is_new && $operation === self::GROUP_FILE_STATISTICS_UPDATE_OPERATION) {
              foreach ($entity->original->get($key)->referencedEntities() as $paragraph) {
                foreach ($field_name as $paragraph_field) {
                  // Sets array of old medias to decrement from group file
                  // statistics.
                  foreach ($paragraph->get($paragraph_field)->referencedEntities() as $media) {
                    $old_medias[$media->id()] = $media;
                  }
                }
              }
            }

            foreach ($entity->get($key)->referencedEntities() as $paragraph) {
              foreach ($field_name as $paragraph_field) {
                // Sets array of new medias to increment to the group file
                // statistics.
                foreach ($paragraph->get($paragraph_field)->referencedEntities() as $media) {

                  // Node is new, so we just need to add the new medias to
                  // increment.
                  if ($group_content_is_new || $operation === self::GROUP_FILE_STATISTICS_DELETE_OPERATION) {
                    $medias[] = $media;
                    continue;
                  }

                  // At this point it means the node alrady exists.
                  if (!isset($old_medias[$media->id()])) {
                    $medias[$media->id()] = $media;
                  }
                  else {
                    unset($old_medias[$media->id()]);

                    // If the node has been unpublished, we add the node
                    // medias to an array so that they get decremented from
                    // file statistics.
                    if ($entity->original->isPublished() && !$entity->isPublished()) {
                      $unpublished_node_medias[$media->id()] = $media;
                    }
                    elseif (!$entity->original->isPublished() && $entity->isPublished()) {
                      // If the node has been published, we add the node
                      // medias to the medias array so that they get
                      // incremented in file statistics.
                      $medias[$media->id()] = $media;
                    }
                  }
                }
              }
            }
          }
        }
        continue;
      }

      if ($entity->hasField($field_name)) {

        // Node is not new, so we need grab the old medias to decrement later.
        if (!$group_content_is_new && $operation === self::GROUP_FILE_STATISTICS_UPDATE_OPERATION) {
          // Sets array of old medias to decrement from group file
          // statistics.
          foreach ($entity->original->get($field_name)->referencedEntities() as $media) {
            $old_medias[$media->id()] = $media;
          }
        }

        // Sets array of new medias to increment to the group file
        // statistics.
        foreach ($entity->get($field_name)->referencedEntities() as $media) {

          if ($group_content_is_new || $operation === self::GROUP_FILE_STATISTICS_DELETE_OPERATION) {
            $medias[] = $media;
            continue;
          }

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

    // Updates group file statistics depending on the node operation: "create",
    // "update" or "delete".
    switch ($operation) {
      case self::GROUP_FILE_STATISTICS_CREATE_OPERATION:
        // This is a new node so we count the new medias.
        // If node has no media entities, we don't need to update group file
        // statistics.
        if (empty($medias)) {
          return FALSE;
        }

        // Counts the number of times we need to decrement the file statistic.
        $count = $this->countGroupFileStatistics($group, $entity, $medias);

        if (!$count) {
          return FALSE;
        }

        // Update group file statistics.
        $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_FILES, $count);

        return TRUE;

      case self::GROUP_FILE_STATISTICS_DELETE_OPERATION:
        // If node has no media entities, we don't need to update group file
        // statistics.
        if (empty($medias)) {
          return FALSE;
        }

        // Counts the number of times we need to decrement the file statistic.
        $count = $this->countGroupFileStatistics($group, $entity, $medias);

        if (!$count) {
          return FALSE;
        }

        // Update group file statistics.
        $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_FILES, $count);

        return TRUE;

      case self::GROUP_FILE_STATISTICS_UPDATE_OPERATION:
        // At this point, it means that the node already exists and we need to
        // count the file statistics taking into consideration new and old
        // medias. If there are no media entities to increment or decrement,
        // then we don't need to update group file statistics.
        if (empty($medias) && empty($old_medias) && empty($unpublished_node_medias)) {
          return FALSE;
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
          return FALSE;
        }

        // Increments group file statistics.
        if ($increment_count) {
          $this->groupStatisticsStorage->increment($group, GroupStatisticTypes::STAT_TYPE_FILES, $increment_count);
        }

        // Decrements group file statistics.
        if ($decrement_count) {
          $this->groupStatisticsStorage->decrement($group, GroupStatisticTypes::STAT_TYPE_FILES, $decrement_count);
        }
        return TRUE;

    }
  }

  /**
   * Counts group file statistics for a given node with medias.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we want to count file statistics.
   * @param \Drupal\Core\Entity\ContentEntityInterface $node
   *   The node entity that belongs to the group.
   * @param \Drupal\media\MediaInterface[] $medias
   *   Array of media entities that belong to the node.
   *
   * @return int
   *   The number of times we need to increment/decrement in the group file
   *   statistics.
   */
  private function countGroupFileStatistics(GroupInterface $group, ContentEntityInterface $node, array $medias = []) {
    $count_updates = 0;

    $source_entity_types = [
      'node',
      'paragraph',
    ];

    if (!$medias) {
      return $count_updates;
    }

    foreach ($medias as $media) {
      // Loads up the entity usage for the media. It includes source entity
      // revisions IDs.
      $media_usage = $this->entityUsage->listSources($media);

      // If there is no media usage, we increment the counter.
      if (!isset($media_usage['node']) && !isset($media_usage['paragraph'])) {
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
      if (
        (
          isset($media_usage['node']) &&
          count($media_usage['node']) > 0
        ) ||
        (
          isset($media_usage['paragraph']) &&
          count($media_usage['paragraph']) > 0
        )
      ) {
        $source_nodes = [];
        $source_node_ids = [];

        foreach ($source_entity_types as $entity_type) {

          // No media usage for the current entity type, so we skip this
          // iteration.
          if (!isset($media_usage[$entity_type])) {
            continue;
          }

          // Because the media usage is saved per revision, we need to make
          // sure the media is presented in the latest revision of every
          // entity. If the media is not presented in the latest revision, that
          // entity will be discarded.
          foreach ($media_usage[$entity_type] as $entity_id => $media_usage_items) {
            $latest_vid = $this->entityTypeManager->getStorage($entity_type)->getLatestRevisionId($entity_id);

            foreach ($media_usage_items as $media_usage_item) {
              if ((int) $media_usage_item['source_vid'] === $latest_vid) {
                $entity_revision = $this->entityTypeManager->getStorage($entity_type)->loadRevision($latest_vid);

                // If the source entity is a paragraph.
                if ($entity_type === 'paragraph') {
                  $paragraph_node = $entity_revision->getParentEntity();

                  // If for some reason the paragraph points to a deleted
                  // parent entity, we skip this one.
                  if (!$paragraph_node) {
                    continue;
                  }

                  // Make sure the paragraph node is published, otherwise we
                  // skip this one.
                  if (!$paragraph_node->isPublished()) {
                    continue;
                  }

                  if ($node->id() === $paragraph_node->id()) {
                    continue;
                  }

                  if (!in_array($paragraph_node->id(), $source_node_ids)) {
                    $source_nodes[] = $paragraph_node;
                    $source_node_ids[] = $paragraph_node->id();
                  }
                  break;
                }

                // Make sure the revision is published, otherwise we skip this
                // node.
                if (!$entity_revision->isPublished()) {
                  continue;
                }

                $source_nodes[] = $entity_revision;
                $source_node_ids[] = $entity_revision->id();

                // Latest revision has been found in the usage. We don't need
                // go through the remaining items.
                break;
              }
            }
          }
        }

        $duplicated_media = FALSE;
        foreach ($source_nodes as $source_node) {
          $group_contents = $this->entityTypeManager
            ->getStorage('group_content')
            ->loadByEntity($source_node);

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
