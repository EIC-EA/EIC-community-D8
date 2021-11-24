<?php

namespace Drupal\eic_statistics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_flags\FlagType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlagService;

/**
 * Helper class around statistics.
 */
class StatisticsHelper {

  /**
   * The eic_statistics.storage service.
   *
   * @var \Drupal\eic_statistics\StatisticsStorage
   */
  protected $statisticsStorage;

  /**
   * The statistics.storage.node service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $nodeStatisticsDatabaseStorage;

  /**
   * The eic_media_statistics.entity_file_download_count service.
   *
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount
   */
  protected $entityFileDownloadCount;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * The eic_comments.helper service.
   *
   * @var \Drupal\eic_comments\CommentsHelper
   */
  protected $commentsHelper;

  /**
   * The eic_user.helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * The eic_user.helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * Constructs a StatisticsHelper object.
   *
   * @param \Drupal\eic_statistics\StatisticsStorage $statistics_storage
   *   The eic_statistics.storage service.
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_storage
   *   The statistics.storage.node service.
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The eic_media_statistics.entity_file_download_count service.
   * @param \Drupal\flag\FlagService $flag_service
   *   The flag service.
   * @param \Drupal\eic_comments\CommentsHelper $comments_helper
   *   The eic_comments.helper service.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The eic_user.helper service.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   *   The eic_groups.helper service.
   */
  public function __construct(
    StatisticsStorage $statistics_storage,
    NodeStatisticsDatabaseStorage $node_statistics_storage,
    EntityFileDownloadCount $entity_file_download_count,
    FlagService $flag_service,
    CommentsHelper $comments_helper,
    UserHelper $user_helper,
    EICGroupsHelper $groups_helper
  ) {
    $this->statisticsStorage = $statistics_storage;
    $this->nodeStatisticsDatabaseStorage = $node_statistics_storage;
    $this->entityFileDownloadCount = $entity_file_download_count;
    $this->flagService = $flag_service;
    $this->commentsHelper = $comments_helper;
    $this->userHelper = $user_helper;
    $this->groupsHelper = $groups_helper;
  }

  /**
   * Returns statistics for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which we get the statistics.
   *
   * @return array
   *   An array with following structure:
   *   - views: Number of views. Applies to nodes only.
   *   - <flag_id>: Number of flags per flag ID. On key/value will be created
   *     per applicable flag.
   *   - downloads: Number of media downloads.
   *   - comments: Number of comments.
   */
  public function getEntityStatistics(EntityInterface $entity) {
    $result = [];

    // Specific statistics per entity type.
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $result['views'] = 0;
        if ($statistics_views_result = $this->nodeStatisticsDatabaseStorage->fetchView($entity->id())) {
          $result['views'] = $statistics_views_result->getTotalCount();
        }
        break;

      case 'taxonomy_term':
        if ($entity->bundle() == Topics::TERM_VOCABULARY_TOPICS_ID) {
          $result['experts'] = count($this->userHelper->getUsersByExpertise($entity));
          // @todo Filter by organisation once available.
          $result['organisations'] = count($this->groupsHelper->getGroupsByTopic($entity, []));
        }
        break;

    }

    // Flags statistics.
    $countable_flags = [
      FlagType::LIKE_CONTENT,
    ];
    foreach ($this->flagService->getAllFlags($entity->getEntityTypeId()) as $flag) {
      if (!in_array($flag->id(), $countable_flags)) {
        continue;
      }
      $result[$flag->id()] = count($this->flagService->getAllEntityFlaggings($entity));
    }

    // Downloads statistics.
    $result['downloads'] = $this->entityFileDownloadCount->getFileDownloads($entity);

    // Comments statistics.
    $result['comments'] = $this->commentsHelper->countEntityComments($entity);
    return $result;
  }

}
