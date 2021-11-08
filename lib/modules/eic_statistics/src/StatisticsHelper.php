<?php

namespace Drupal\eic_statistics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_flags\FlagType;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
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
   */
  public function __construct(
    StatisticsStorage $statistics_storage,
    NodeStatisticsDatabaseStorage $node_statistics_storage,
    EntityFileDownloadCount $entity_file_download_count,
    FlagService $flag_service,
    CommentsHelper $comments_helper) {
    $this->statisticsStorage = $statistics_storage;
    $this->nodeStatisticsDatabaseStorage = $node_statistics_storage;
    $this->entityFileDownloadCount = $entity_file_download_count;
    $this->flagService = $flag_service;
    $this->commentsHelper = $comments_helper;
  }

  /**
   * Returns statistics for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which we get the statistics.
   *
   * @return array
   *   An array with following structure:
   */
  public function getEntityStatistics(EntityInterface $entity) {
    $result = [];

    // Views statistics.
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $result['views'] = $this->nodeStatisticsDatabaseStorage->fetchView($entity->id())->getTotalCount();
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
