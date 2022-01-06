<?php

namespace Drupal\eic_group_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\comment\CommentInterface;
use Drupal\eic_comments\Constants\Comments;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Service that provides helper functions for groups statistics.
 */
class GroupStatisticsHelper implements GroupStatisticsHelperInterface {

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Group statistics storage service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsStorageInterface
   */
  protected $groupStatisticsStorage;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * Constructs a GroupStatisticsHelper object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsStorageInterface $group_statistics_storage
   *   The Group statistics storage service.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   *   The EIC group statistics helper service.
   */
  public function __construct(
    CacheBackendInterface $cache_backend,
    EntityTypeManagerInterface $entity_type_manager,
    GroupStatisticsStorageInterface $group_statistics_storage,
    EICGroupsHelper $groups_helper
  ) {
    $this->cacheBackend = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStatisticsStorage = $group_statistics_storage;
    $this->groupsHelper = $groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function loadGroupStatistics(GroupInterface $group) {
    return $this->groupStatisticsStorage->load($group);
  }

  /**
   * {@inheritdoc}
   */
  public function loadGroupStatisticsFromSearchIndex(GroupInterface $group) {
    /** @var \Drupal\search_api\IndexInterface $search_api_index */
    $search_api_index = $this->entityTypeManager->getStorage('search_api_index')->load('global');

    // Create the query.
    $query = $search_api_index->query();
    // Filter by group ID.
    $query->addCondition('group_id_integer', $group->id());
    // Limit query to 1 result.
    $query->range(0, 1);

    $results = $query->execute();

    // If there are no results we return empty group statistics.
    if (!$results->getResultCount()) {
      return new GroupStatistics($group->id());
    }

    // Gets first result item found.
    $items = $results->getResultItems();
    $item = reset($items);

    return new GroupStatistics(
      (int) $group->id(),
      $item->getField('group_statistic_members')->getValues()[0],
      $item->getField('group_statistic_comments')->getValues()[0],
      $item->getField('group_statistic_files')->getValues()[0],
      $item->getField('group_statistic_events')->getValues()[0],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateAllGroupsStatistics() {
    /**
     * Loads all groups.
     *
     * @var \Drupal\group\Entity\GroupInterface[] $groups
     */
    $groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    /**
     * Initializes array of group statistics.
     *
     * @var \Drupal\eic_group_statistics\GroupStatistics[] $groups_statistics
     */
    $groups_statistics = [];

    // Calculate statistics for each group.
    foreach ($groups as $group) {
      $groups_statistics[] = $this->groupStatisticsStorage->calculateGroupStatistics($group);
    }

    // Update groups statistics.
    $this->groupStatisticsStorage->setMultipleGroupStatistics($groups_statistics);
  }

  /**
   * Returns the date of the latest content activity for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $conditions
   *   An array of conditions to apply to the group_content query, with field as
   *   key and value as the value.
   *   E.g. entity_id.entity:node.status => 1
   *   Or entity_id.entity:node.uid => 123
   *   Check \Drupal\Core\Entity\Query\QueryInterface.
   *
   * @return int|null
   *   A timestamp or null if there are no results.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function queryGroupLatestContentActivity(GroupInterface $group, array $conditions = []) {
    $content_plugins = $this->groupsHelper->getGroupTypeEnabledContentPlugins($group->getGroupType());
    $group_content_storage = $this->entityTypeManager->getStorage('group_content');

    // We need to query on group_content entities to get the latest node.
    $query = $group_content_storage->getQuery();
    $query->condition('type', $content_plugins, 'IN');
    $query->condition('gid', $group->id());
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $query->sort('entity_id.entity:node.created', 'DESC');
    $query->range(0, 1);
    $results = $query->execute();

    if (!empty($results)) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_content = $this->entityTypeManager->getStorage('group_content')->load(reset($results));
      /** @var \Drupal\node\NodeInterface $node */
      $node = $group_content->getEntity();
      return $node->getCreatedTime();
    }

    return NULL;
  }

  /**
   * Returns the count of contents for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $conditions
   *   An array of conditions to apply to the group_content query, with field as
   *   key and value as the value.
   *   E.g. entity_id.entity:node.status => 1
   *   Or entity_id.entity:node.uid => 123
   *   Check \Drupal\Core\Entity\Query\QueryInterface.
   *
   * @return int|null
   *   A timestamp or null if there are no results.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupContentCount(GroupInterface $group, array $conditions = []) {
    $content_plugins = $this->groupsHelper->getGroupTypeEnabledContentPlugins($group->getGroupType());
    $group_content_storage = $this->entityTypeManager->getStorage('group_content');

    // We need to query on group_content entities.
    $query = $group_content_storage->getQuery();
    $query->condition('type', $content_plugins, 'IN');
    $query->condition('gid', $group->id());
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $query->count();
    return $query->execute();
  }

  /**
   * Returns the date of the latest comment activity for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $conditions
   *   An array of conditions to apply to the comment query, with field as
   *   key and value as the value.
   *   E.g. entity_id.entity:node.status => 1
   *   Or entity_id.entity:node.uid => 123
   *   Check \Drupal\Core\Entity\Query\QueryInterface.
   *
   * @return int|null
   *   A timestamp or null if there are no results.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function queryGroupLatestCommentActivity(GroupInterface $group, array $conditions = []) {
    $content_plugins = $this->groupsHelper->getGroupTypeEnabledContentPlugins($group->getGroupType());
    $group_content_storage = $this->entityTypeManager->getStorage('group_content');

    // We need to query on group_content entities to get the list of nodes that
    // may contain comments.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $group_content_storage->getQuery();
    $query->condition('type', $content_plugins, 'IN');
    $query->condition('gid', $group->id());
    $query->exists('entity_id.entity:node.' . Comments::DEFAULT_NODE_COMMENTS_FIELD);
    $results = $query->execute();

    // Get the list of related nodes.
    $node_ids = [];
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    foreach ($group_content_storage->loadMultiple($results) as $group_content) {
      $node_ids[] = $group_content->getEntity()->id();
    }

    // Now we can query comments based on the list of nodes.
    if (!empty($node_ids)) {
      $comment_storage = $this->entityTypeManager->getStorage('comment');
      $query = $comment_storage->getQuery();
      $query->condition('entity_id', $node_ids, 'IN');
      $query->condition('comment_type', Comments::DEFAULT_NODE_COMMENTS_TYPE);
      $query->condition('field_name', Comments::DEFAULT_NODE_COMMENTS_FIELD);
      $query->condition('status', CommentInterface::PUBLISHED);
      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }
      $query->sort('created', 'DESC');
      $query->range(0, 1);
      $results = $query->execute();

      // Return the created date of the last comment.
      if (!empty($results)) {
        /** @var \Drupal\comment\CommentInterface $comment */
        $comment = $this->entityTypeManager->getStorage('comment')->load(reset($results));
        return $comment->getCreatedTime();
      }
    }

    return NULL;
  }

  /**
   * Returns the latest activity timestamp for the given group.
   *
   * Latest activity is calculated based on published nodes/comments.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return int|null
   *   The timestamp of the latest activity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupLatestActivity(GroupInterface $group) {
    $cid = 'group_latest_activity:' . $group->id();

    // Look for the item in cache.
    if ($item = $this->cacheBackend->get($cid)) {
      return $item->data;
    }

    // Get the highest value for activity.
    $activities = [];
    $conditions = [
      'entity_id.entity:node.status' => NodeInterface::PUBLISHED,
    ];
    $activities[] = $this->queryGroupLatestContentActivity($group, $conditions);
    $activities[] = $this->queryGroupLatestCommentActivity($group);
    $latest_activity = max($activities);

    // Cache the result.
    $this->cacheBackend->set($cid, $latest_activity, Cache::PERMANENT);

    return $latest_activity;
  }

  /**
   * Returns the latest activity timestamp for the given group.
   *
   * Latest activity is calculated based on published nodes/comments.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return int|null
   *   The timestamp of the latest activity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupMemberLatestActivity(GroupInterface $group, UserInterface $user) {
    $cid = 'group_member_latest_activity:' . $group->id() . ':' . $user->id();

    // Look for the item in cache.
    if ($item = $this->cacheBackend->get($cid)) {
      return $item->data;
    }

    // Get the highest value for activity.
    $activities = [];
    $content_conditions = [
      'entity_id.entity:node.status' => NodeInterface::PUBLISHED,
      'entity_id.entity:node.uid' => $user->id(),
    ];
    $comment_conditions = [
      'uid' => $user->id(),
    ];
    $activities[] = $this->queryGroupLatestContentActivity($group, $content_conditions);
    $activities[] = $this->queryGroupLatestCommentActivity($group, $comment_conditions);
    $latest_activity = max($activities);

    // Cache the result.
    $this->cacheBackend->set($cid, $latest_activity, Cache::PERMANENT);

    return $latest_activity;
  }

  /**
   * Invalidates the latest activity cache for the given group entity.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\user\UserInterface[] $users
   *   An array of user objects for which we want to invalidate their group
   *   activity cache.
   */
  public function invalidateGroupLatestActivity(GroupInterface $group, array $users = []) {
    // Invalidate cache for group latest activity.
    $cid = 'group_latest_activity:' . $group->id();
    $this->cacheBackend->invalidate($cid);

    foreach ($users as $user) {
      $cid = 'group_member_latest_activity:' . $group->id() . ':' . $user->id();
      $this->cacheBackend->invalidate($cid);
    }
  }

}
