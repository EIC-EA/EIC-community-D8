<?php

namespace Drupal\eic_search\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\eic_search\SolrIndexes;
use Drupal\group\Entity\GroupInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\PostRequestIndexing;
use Drupal\search_api\Utility\Utility;

/**
 * Class SolrDocumentProcessor
 *
 * @package Drupal\eic_search\Service
 */
class SolrDocumentProcessor {

  /**
   * The Search API Post request indexing service.
   *
   * @var \Drupal\search_api\Utility\PostRequestIndexing
   */
  private $postRequestIndexing;

  /**
   * The Queue Factory service.
   *
   * @var QueueFactory $queueFactory
   */
  private $queueFactory;

  /**
   * The Queue Worker Manager service.
   *
   * @var QueueWorkerManager $queueManager
   */
  private $queueManager;

  /**
   * The Entity Type Manager service.
   *
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  private $entityTypeManager;

  /**
   * The key used to identify solr document fields for last flagged.
   *
   * @var string
   */
  const LAST_FLAGGED_KEY = 'last_flagged';

  /**
   * SolrDocumentProcessor constructor.
   *
   * @param \Drupal\search_api\Utility\PostRequestIndexing $post_request_indexing
   *   The Search API Post request indexing service.
   * @param QueueFactory $queue_factory
   *   The Queue Factory service.
   * @param QueueWorkerManager $queue_worker_manager
   *   The Queue Worker Manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct(
    PostRequestIndexing $post_request_indexing,
    QueueFactory $queue_factory,
    QueueWorkerManager $queue_worker_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->postRequestIndexing = $post_request_indexing;
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_worker_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Requests reindexing of the given entities.
   *
   * @param EntityInterface[] $items
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function reIndexEntities(array $items) {
    $global_index = Index::load(SolrIndexes::GLOBAL);
    $item_ids = [];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($items as $entity) {
      if (!$entity instanceof EntityInterface) {
        continue;
      }
      $datasource_id = 'entity:' . $entity->getEntityTypeId();

      try {
        $datasource = $global_index->getDatasource($datasource_id);
      } catch (SearchApiException $api_exception) {
        continue;
      }

      $item_id = $datasource->getItemId($entity->getTypedData());
      $item_ids[] = Utility::createCombinedId($datasource_id, $item_id);
    }

    // Request reindexing for the given items.
    $this->postRequestIndexing->registerIndexingOperation(SolrIndexes::GLOBAL, $item_ids);
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function reIndexEntitiesFromGroup(GroupInterface $group) {
    $queue = $this->queueFactory->get('eic_groups_group_content_search_api');
    $queue_worker = $this->queueManager->createInstance('eic_groups_group_content_search_api');

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');
    $contents = $storage->loadByGroup($group);

    foreach ($contents as $group_content) {
      $queue->createItem($group_content);
    }

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
    }
  }

}
