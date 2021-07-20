<?php

namespace Drupal\eic_group_statistics;

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
      case GroupStatisticsStorageInterface::STAT_TYPE_MEMBERS:
      case GroupStatisticsStorageInterface::STAT_TYPE_COMMENTS:
      case GroupStatisticsStorageInterface::STAT_TYPE_FILES:
      case GroupStatisticsStorageInterface::STAT_TYPE_EVENTS:
        $query->fields([$statistic_type => 1])
          ->expression($statistic_type, "[$statistic_type] + :count", [':count' => $count])
          ->execute();
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
      case GroupStatisticsStorageInterface::STAT_TYPE_MEMBERS:
      case GroupStatisticsStorageInterface::STAT_TYPE_COMMENTS:
      case GroupStatisticsStorageInterface::STAT_TYPE_FILES:
      case GroupStatisticsStorageInterface::STAT_TYPE_EVENTS:
        $query->fields([$statistic_type => 1])
          ->expression($statistic_type, "[$statistic_type] - :count", [':count' => $count])
          ->execute();
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

}
