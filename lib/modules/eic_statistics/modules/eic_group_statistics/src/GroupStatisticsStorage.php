<?php

namespace Drupal\eic_group_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides the default storage backend for Group statistics.
 */
class GroupStatisticsStorage implements GroupStatisticsStorageInterface {

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new GroupStatisticsStorage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The current active database's master connection.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    Connection $connection,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->connection = $connection;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function increment(GroupInterface $group, $statistic_type, $count = 1) {
    $query = $this->connection->merge('eic_group_statistics')
      ->keys([
        'gid' => $group->id(),
      ])
      ->fields([
        'gtype' => $group->bundle(),
      ]);

    switch ($statistic_type) {
      case GroupStatisticTypes::STAT_TYPE_MEMBERS:
      case GroupStatisticTypes::STAT_TYPE_COMMENTS:
      case GroupStatisticTypes::STAT_TYPE_FILES:
      case GroupStatisticTypes::STAT_TYPE_EVENTS:
        $query->fields([$statistic_type => 1])
          ->expression($statistic_type, "[$statistic_type] + :count", [':count' => $count])
          ->execute();

        // Invalidate group cache tags.
        Cache::invalidateTags($group->getCacheTagsToInvalidate());
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrement(GroupInterface $group, $statistic_type, $count = 1) {
    $query = $this->connection->merge('eic_group_statistics')
      ->keys([
        'gid' => $group->id(),
      ])
      ->fields([
        'gtype' => $group->bundle(),
      ]);

    switch ($statistic_type) {
      case GroupStatisticTypes::STAT_TYPE_MEMBERS:
      case GroupStatisticTypes::STAT_TYPE_COMMENTS:
      case GroupStatisticTypes::STAT_TYPE_FILES:
      case GroupStatisticTypes::STAT_TYPE_EVENTS:
        $query->fields([$statistic_type => 1])
          ->expression($statistic_type, "[$statistic_type] - :count", [':count' => $count])
          ->execute();

        // Invalidate group cache tags.
        Cache::invalidateTags($group->getCacheTagsToInvalidate());
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteGroupStatistics(GroupInterface $group) {
    $this->connection->delete('eic_group_statistics')
      ->condition('gid', $group->id())
      ->execute();

    // Invalidate group cache tags.
    Cache::invalidateTags($group->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group) {
    $result = $this->connection->select('eic_group_statistics')
      ->fields('eic_group_statistics')
      ->condition('gid', $group->id())
      ->range(0, 1)
      ->execute()->fetchAssoc();

    if (empty($result)) {
      return new GroupStatistics($group->id());
    }

    return new GroupStatistics(
      $result['gid'],
      $result['members'],
      $result['comments'],
      $result['files'],
      $result['events'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupStatistics(GroupStatistics $group_statistics) {
    $this->connection->merge('eic_group_statistics')
      ->key('gid', $group_statistics->getGroupId())
      ->fields([
        'members' => $group_statistics->getMembersCount(),
        'comments' => $group_statistics->getCommentsCount(),
        'files' => $group_statistics->getFilesCount(),
        'events' => $group_statistics->getEventsCount(),
      ])
      ->execute();

    // Invalidate group cache tags.
    Cache::invalidateTags(['group:' . $group_statistics->getGroupId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultipleGroupStatistics(array $groups_statistics) {
    foreach ($groups_statistics as $group_statistics) {
      $this->setGroupStatistics($group_statistics);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateGroupStatistics(GroupInterface $group) {
    $members_count = $this->calculateGroupMembersStatistics($group);
    $comments_count = $this->calculateGroupCommentsStatistics($group);
    $files_count = $this->calculateGroupFilesStatistics($group);
    $events_count = $this->calculateGroupEventsStatistics($group);

    return new GroupStatistics(
      (int) $group->id(),
      (int) $members_count,
      (int) $comments_count,
      (int) $files_count,
      (int) $events_count
    );
  }

  /**
   * Calculates number of group members to add to the statistics.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return int
   *   The number of group members.
   */
  private function calculateGroupMembersStatistics(GroupInterface $group) {
    // Query to count number of members.
    $query_members = $this->connection->select('group_content_field_data', 'gc_fd')
      ->fields('gc_fd', ['gid'])
      ->condition('gc_fd.gid', $group->id())
      ->condition('gc_fd.type', "{$group->bundle()}-group_membership");
    $query_members->addExpression('COUNT(gc_fd.entity_id)', 'count');
    return $query_members->execute()->fetchAssoc()['count'];
  }

  /**
   * Calculates number of group comments to add to the statistics.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return int
   *   The number of comments in the group.
   */
  private function calculateGroupCommentsStatistics(GroupInterface $group) {
    $comment_ctypes = [
      "{$group->bundle()}-group_node-discussion",
      "{$group->bundle()}-group_node-document",
      "{$group->bundle()}-group_node-gallery",
      "{$group->bundle()}-group_node-wiki_page",
    ];
    // Query to count number of comments.
    $query_comments = $this->connection->select('group_content_field_data', 'gc_fd')
      ->fields('gc_fd', ['gid'])
      ->condition('gc_fd.gid', $group->id())
      ->condition('gc_fd.type', $comment_ctypes, 'IN');
    $query_comments->join('node_field_data', 'n_fd', 'gc_fd.entity_id = n_fd.nid');
    // We calculate statistics only for published nodes.
    $query_comments->condition('n_fd.status', TRUE);
    $query_comments->join('comment_field_data', 'c_fd', 'gc_fd.entity_id = c_fd.entity_id');
    $query_comments->addExpression('COUNT(c_fd.entity_id)', 'count');
    return $query_comments->execute()->fetchAssoc()['count'];
  }

  /**
   * Calculates number of group files to add to the statistics.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return int
   *   The number of files in the group.
   */
  private function calculateGroupFilesStatistics(GroupInterface $group) {
    // Initialize array of media target ids that will be used to count the
    // number of files in group.
    $media_target_ids = [];
    // Array of group content to count file statistics.
    $files_group_content_types = [
      'discussion',
      'document',
      'gallery',
      'wiki_page',
    ];
    // Get array of fields that will be used to count the group file
    // statistics.
    $file_statistic_fields = self::getGroupFileStatisticFields();

    // We want to count the number of files for each group content that
    // contains media files. Note that we take into account multiple media
    // reference fields and also paragraph fields that contain media.
    foreach ($files_group_content_types as $node_type) {
      // Get all field definitions of the node type.
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $node_type);

      foreach ($file_statistic_fields as $field_name_key => $field_name) {

        // Field doesn't exist in this node type, so we can skip it.
        if (!isset($field_definitions[$field_name_key])) {
          continue;
        }

        // If the current value of $field_name is an array of media fields, we
        // assume this field as an entity reference field and so we count the
        // file statistics on the entity reference level. Currently it works
        // only with paragraph fields.
        if (is_array($field_name)) {
          $media_target_ids = $this->calculateEntityReferenceFieldFileStatistics($group, $node_type, $field_name_key, $field_name, $media_target_ids);
          continue;
        }

        // The field is a media reference field so we count the file statistics
        // for the media.
        $media_target_ids = $this->calculateMediaReferenceFieldFileStatistics($group, $node_type, $field_name, $media_target_ids);
      }
    }
    return count($media_target_ids);
  }

  /**
   * Calculates files statistics for a given media reference field.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   * @param string $node_type
   *   The node bundle name.
   * @param string $field_name
   *   The media reference field name.
   * @param array $media_target_ids
   *   The array of media entity IDs that was already calculated.
   *
   * @return array
   *   The updated array of media entity IDs.
   */
  private function calculateMediaReferenceFieldFileStatistics(GroupInterface $group, $node_type, $field_name, array $media_target_ids) {
    $group_content_type = "{$group->bundle()}-group_node-{$node_type}";

    // Query to count number of files per group content + media fields.
    $query_files = $this->connection->select('group_content_field_data', 'gc_fd')
      ->fields('n_field', ["{$field_name}_target_id"])
      ->condition('gc_fd.gid', $group->id())
      ->condition('gc_fd.type', $group_content_type, 'IN');

    // We discard medias that already been counted.
    if (!empty($media_target_ids)) {
      $query_files->condition("n_field.{$field_name}_target_id", $media_target_ids, 'NOT IN');
    }

    $query_files->join("node__{$field_name}", 'n_field', 'gc_fd.entity_id = n_field.entity_id');
    $query_files->join("node_field_data", 'n_fd', 'gc_fd.entity_id = n_fd.nid');
    // We calculate statistics only for published nodes.
    $query_files->condition('n_fd.status', TRUE);
    $query_files->groupBy("n_field.{$field_name}_target_id");
    $target_ids = $query_files->execute()->fetchAll();

    // We update the array of media target IDs with IDs by the query so that
    // we can skip those in future queries.
    if (!empty($target_ids)) {
      foreach ($target_ids as $target_id) {
        $field_name_target_id = "{$field_name}_target_id";
        $media_target_ids[] = $target_id->$field_name_target_id;
      }
    }
    return $media_target_ids;
  }

  /**
   * Calculates files statistics for a given entity reference field.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   * @param string $node_type
   *   The node bundle name.
   * @param string $reference_field_name
   *   The entity reference field name.
   * @param array $fields
   *   The entity reference fields containing the media field names.
   * @param array $media_target_ids
   *   The array of media entity IDs that was already calculated.
   *
   * @return array
   *   The updated array of media entity IDs.
   */
  private function calculateEntityReferenceFieldFileStatistics(GroupInterface $group, $node_type, $reference_field_name, array $fields, array $media_target_ids) {
    $group_content_type = "{$group->bundle()}-group_node-{$node_type}";

    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $node_type);

    if (!isset($field_definitions[$reference_field_name])) {
      return $media_target_ids;
    }

    // @todo We currently support only paragraph entities. We need to improve
    // it in order to support all entity types.
    if (
      $field_definitions[$reference_field_name]->getType() !== 'entity_reference_revisions'
    ) {
      return $media_target_ids;
    }

    foreach ($fields as $field_name) {
      // Query to count number of files per group content + media fields.
      $query_files = $this->connection->select('group_content_field_data', 'gc_fd')
        ->fields('p_field', ["{$field_name}_target_id"])
        ->condition('gc_fd.gid', $group->id())
        ->condition('gc_fd.type', $group_content_type, 'IN');

      // Joins reference field table of the group content node.
      $query_files->join("node__{$reference_field_name}", 'n_field', 'gc_fd.entity_id = n_field.entity_id');
      $query_files->join("node_field_data", 'n_fd', 'gc_fd.entity_id = n_fd.nid');

      // Joins the paragraph field table.
      $query_files->join("paragraphs_item_field_data", 'p_fd', 'p_fd.parent_id = n_fd.nid');
      $query_files->join("paragraph__{$field_name}", 'p_field', 'p_field.entity_id = p_fd.id');

      // We discard medias that already been counted.
      if (!empty($media_target_ids)) {
        $query_files->condition("p_field.{$field_name}_target_id", $media_target_ids, 'NOT IN');
      }

      // We calculate statistics only for published nodes.
      $query_files->condition('n_fd.status', TRUE);
      // We group results by media target ID.
      $query_files->groupBy("p_field.{$field_name}_target_id");
      $target_ids = $query_files->execute()->fetchAll();

      // We update the array of media target IDs with IDs by the query so that
      // we can skip those in future queries.
      if (!empty($target_ids)) {
        foreach ($target_ids as $target_id) {
          $field_name_target_id = "{$field_name}_target_id";
          $media_target_ids[] = $target_id->$field_name_target_id;
        }
      }
    }

    return $media_target_ids;
  }

  /**
   * Calculates number of group events to add to the statistics.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return int
   *   The number of comments in the group.
   */
  private function calculateGroupEventsStatistics(GroupInterface $group) {
    // @todo Implement logic once we have the group type Event available.
    return 0;
  }

  /**
   * Gets the list of fields used to calculate group file statistics.
   *
   * @return array
   *   Array of media and entity reference fields.
   *   - key: field machine name
   *   - value: field machine name or array of field machine names in case of
   *   entity reference fields.
   */
  public static function getGroupFileStatisticFields() {
    return [
      'field_document_media' => 'field_document_media',
      'field_related_downloads' => 'field_related_downloads',
      'field_related_documents' => 'field_related_documents',
      'field_gallery_slides' => [
        'field_gallery_slide_media',
      ],
    ];
  }

}
