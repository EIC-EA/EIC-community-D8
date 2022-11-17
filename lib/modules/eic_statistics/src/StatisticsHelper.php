<?php

namespace Drupal\eic_statistics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_flags\FlagType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\flag\FlagService;
use Drupal\statistics\NodeStatisticsDatabaseStorage;

/**
 * Helper class around statistics.
 */
class StatisticsHelper {

  /**
   * State cache name of node ids to update the view counter in SOLR.
   */
  const NODE_VIEW_COUNTER_REINDEX_STATE_CACHE =  'eic_statistics_reindex_node_view_counter';

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
  protected CommentsHelper $commentsHelper;

  /**
   * The eic_user.helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected UserHelper $userHelper;

  /**
   * The eic_user.helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected EICGroupsHelper $groupsHelper;

  /**
   * The flag count manager service.
   *
   * @var FlagCountManagerInterface $flagCountManager
   */
  protected FlagCountManagerInterface $flagCountManager;

  /**
   * @param \Drupal\eic_statistics\StatisticsStorage $statistics_storage
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_storage
   * @param \Drupal\flag\FlagService $flag_service
   * @param \Drupal\eic_comments\CommentsHelper $comments_helper
   * @param \Drupal\eic_user\UserHelper $user_helper
   * @param FlagCountManagerInterface $flag_count_manager
   */
  public function __construct(
    StatisticsStorage $statistics_storage,
    NodeStatisticsDatabaseStorage $node_statistics_storage,
    FlagService $flag_service,
    CommentsHelper $comments_helper,
    UserHelper $user_helper,
    FlagCountManagerInterface $flag_count_manager
  ) {
    $this->statisticsStorage = $statistics_storage;
    $this->nodeStatisticsDatabaseStorage = $node_statistics_storage;
    $this->flagService = $flag_service;
    $this->commentsHelper = $comments_helper;
    $this->userHelper = $user_helper;
    $this->flagCountManager = $flag_count_manager;
  }

  /**
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $file_download_counter
   */
  public function setFileDownloadCounter(
    EntityFileDownloadCount $file_download_counter
  ) {
    $this->entityFileDownloadCount = $file_download_counter;
  }

  /**
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   */
  public function setGroupsHelper(
    EICGroupsHelper $eic_groups_helper
  ) {
    $this->groupsHelper = $eic_groups_helper;
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
      FlagType::FOLLOW_CONTENT
    ];
    foreach ($this->flagService->getAllFlags($entity->getEntityTypeId()) as $flag) {
      if (!in_array($flag->id(), $countable_flags)) {
        continue;
      }

      $entity_flag_counts = $this->flagCountManager->getEntityFlagCounts($entity);
      $result[$flag->id()] = $entity_flag_counts[$flag->id()] ?? 0;
    }

    // Downloads statistics.
    $result['downloads'] = $this->entityFileDownloadCount->getFileDownloads($entity);

    // Comments statistics.
    $result['comments'] = $this->commentsHelper->countEntityComments($entity);
    return $result;
  }

}
