<?php

namespace Drupal\eic_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\eic_statistics\StatisticsHelper;
use Drupal\eic_statistics\StatisticsStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Cron.
 *
 * Implementations for hook_cron().
 */
class CronOperations implements ContainerInjectionInterface {

  /**
   * The EIC statistics storage.
   *
   * @var \Drupal\eic_statistics\StatisticsStorageInterface
   */
  protected $statisticsStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The SOLR Document Processor service.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor
   */
  protected $solrDocumentProcessor;

  /**
   * Constructs a Cron object.
   *
   * @param \Drupal\eic_statistics\StatisticsStorageInterface $statistics_storage
   *   The EIC statistics storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor $solr_document_processor
   *   The SOLR Document Processor service.
   */
  public function __construct(
    StatisticsStorageInterface $statistics_storage,
    EntityTypeManagerInterface $entity_type_manager,
    StateInterface $state,
    SolrDocumentProcessor $solr_document_processor
  ) {
    $this->statisticsStorage = $statistics_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->solrDocumentProcessor = $solr_document_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_statistics.storage'),
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('eic_search.solr_document_processor')
    );
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $this->updateEntityCountersStatistic();
    $this->reindexNodeViewCountersStatistic();
  }

  /**
   * Updates counters statistic for each tracked entity.
   */
  public function updateEntityCountersStatistic() {
    foreach ($this->statisticsStorage->getTrackedEntities() as $entity_type => $bundles) {
      // Updates the counter for each entity bundle.
      // We need this condition because the user
      // entity doesn't have any bundles.
      if (is_array($bundles)) {
        foreach ($bundles as $bundle) {
          $count = $this->statisticsStorage->countTotalEntities($entity_type, $bundle);
          $this->statisticsStorage->updateEntityCounter($count, $entity_type, $bundle);
        }
      }
      else {
        $count = $this->statisticsStorage->countTotalEntities($entity_type);
        $this->statisticsStorage->updateEntityCounter($count, $entity_type);
      }
    }
  }

  /**
   * Re-indexes node view counter statistics.
   */
  public function reindexNodeViewCountersStatistic() {
    $node_view_counter_state_cache = $this->state->get(StatisticsHelper::NODE_VIEW_COUNTER_REINDEX_STATE_CACHE, []);
    $this->state->delete(StatisticsHelper::NODE_VIEW_COUNTER_REINDEX_STATE_CACHE);
    if (empty($node_view_counter_state_cache)) {
      return;
    }

    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($node_view_counter_state_cache);

    $this->solrDocumentProcessor->reIndexEntities($nodes);
  }

}
