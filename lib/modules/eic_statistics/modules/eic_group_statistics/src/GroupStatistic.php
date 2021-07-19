<?php

namespace Drupal\eic_group_statistics;

use Drupal\group\Entity\Group;

/**
 * Value object for passing group statistic results.
 */
class GroupStatistic {

  /**
   * The group entity ID.
   *
   * @var int
   */
  protected $groupId;

  /**
   * Statistic - members counter.
   *
   * @var int
   */
  protected $membersCount;

  /**
   * Statistic - comments counter.
   *
   * @var int
   */
  protected $commentsCount;

  /**
   * Statistic - files counter.
   *
   * @var int
   */
  protected $filesCount;

  /**
   * Statistic - events counter.
   *
   * @var int
   */
  protected $eventsCount;

  /**
   * Constructs a new GroupStatistic object.
   *
   * @param int $group_id
   *   The group entity ID.
   * @param int $members_count
   *   The number of members.
   * @param int $comments_count
   *   The number of comments.
   * @param int $files_count
   *   The number of files.
   * @param int $events_count
   *   The number of events.
   */
  public function __construct(
    $group_id,
    $members_count = 1,
    $comments_count = 0,
    $files_count = 0,
    $events_count = 0
  ) {
    $this->groupId = $group_id;
    $this->membersCount = (int) $members_count;
    $this->commentsCount = (int) $comments_count;
    $this->filesCount = (int) $files_count;
    $this->eventsCount = (int) $events_count;
  }

  /**
   * Gets the group entity object.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group entity.
   */
  public function getGroup() {
    return Group::load($this->groupId);
  }

  /**
   * Gets the group entity ID.
   *
   * @return int
   *   The group entity ID.
   */
  public function getGroupId() {
    return $this->groupId;
  }

  /**
   * Gets the number of group members.
   *
   * @return int
   *   The number of members.
   */
  public function getMembersCount() {
    return $this->membersCount;
  }

  /**
   * Gets the total number of comments in the group.
   *
   * @return int
   *   The number of comments.
   */
  public function getCommentsCount() {
    return $this->commentsCount;
  }

  /**
   * Gets the total number of files in the group.
   *
   * @return int
   *   The number of files.
   */
  public function getFilesCount() {
    return $this->filesCount;
  }

  /**
   * Gets the total number of events in the group.
   *
   * @return int
   *   The number of evenets.
   */
  public function getEventsCount() {
    return $this->eventsCount;
  }

}
