<?php

namespace Drupal\eic_group_statistics;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Service that provides helper functions for groups statistics.
 */
class GroupStatisticsHelper implements GroupStatisticsHelperInterface {

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
   * Constructs a GroupStatisticsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsStorageInterface $group_statistics_storage
   *   The Group statistics storage service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupStatisticsStorageInterface $group_statistics_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupStatisticsStorage = $group_statistics_storage;
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

}
