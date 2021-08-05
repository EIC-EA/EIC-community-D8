<?php

namespace Drupal\eic_group_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
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
   * Constructs a new GroupStatisticsStorage object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The current active database's master connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
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
      return new GroupStatistics($result['gid']);
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
    $query = $this->connection->merge('eic_group_statistics')
      ->key('gid');

    $fields = [];
    foreach ($groups_statistics as $group_statistics) {
      $this->setGroupStatistics($group_statistics);
      // We make sure the array item is an instance of GroupStatistics,
      // otherwise we skip it.
      if (!($group_statistics instanceof GroupStatistics)) {
        continue;
      }
      $fields[] = [
        'gid' => $group_statistics->getGroupId(),
        'members' => $group_statistics->getMembersCount(),
        'comments' => $group_statistics->getCommentsCount(),
        'files' => $group_statistics->getFilesCount(),
        'events' => $group_statistics->getEventsCount(),
      ];
    }

    // Nothing to update. We can exit.
    if (empty($fields)) {
      return;
    }

    $query->fields($fields)
      ->execute();

    // Invalidate group cache tags.
    Cache::invalidateTags(['group:' . $group_statistics->getGroupId()]);
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
    // Array of media fields per group content where we want to count file
    // statistics. Keyed by group content bundle.
    $files_group_content_types = [
      "{$group->bundle()}-group_node-discussion" => [
        'field_related_documents',
      ],
      "{$group->bundle()}-group_node-document" => [
        'field_document_media',
      ],
      "{$group->bundle()}-group_node-gallery" => [
        'field_photos',
      ],
      "{$group->bundle()}-group_node-wiki_page" => [
        'field_related_downloads',
      ],
    ];
    // We want to count the number of files for each group content that
    // contains media files. Note that we take into account multiple media
    // reference fields.
    foreach ($files_group_content_types as $ctype => $fields) {
      foreach ($fields as $field_name) {
        // Query to count number of files per group content + media fields.
        $query_files = $this->connection->select('group_content_field_data', 'gc_fd')
          ->fields('n_field', ["{$field_name}_target_id"])
          ->condition('gc_fd.gid', $group->id())
          ->condition('gc_fd.type', $ctype, 'IN');

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

        // We save the media target ids returned by the query so we skip those
        // in the next queries.
        if (!empty($target_ids)) {
          foreach ($target_ids as $target_id) {
            $field_name_target_id = "{$field_name}_target_id";
            $media_target_ids[] = $target_id->$field_name_target_id;
          }
        }
      }
    }
    return count($media_target_ids);
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

}
