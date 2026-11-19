<?php

namespace Drupal\eic_search\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_search\Collector\DocumentProcessorCollector;
use Drupal\eic_search\Plugin\search_api\processor\GroupAccessContent;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent;
use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Solarium\QueryType\Select\Query\Query as SolrQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replaces the hooks removed in search_api_solr 4.3.
 *
 * Replaces hook_search_api_solr_documents_alter() and
 * hook_search_api_solr_query_alter().
 */
class SearchApiSolrSubscriber implements EventSubscriberInterface {

  /**
   * The document processor collector.
   *
   * @var \Drupal\eic_search\Collector\DocumentProcessorCollector
   */
  private DocumentProcessorCollector $processorCollector;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new SearchApiSolrSubscriber.
   *
   * @param \Drupal\eic_search\Collector\DocumentProcessorCollector $processor_collector
   *   The document processor collector.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DocumentProcessorCollector $processor_collector, EntityTypeManagerInterface $entity_type_manager) {
    $this->processorCollector = $processor_collector;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENTS => 'postCreateIndexDocuments',
      SearchApiSolrEvents::PRE_QUERY => 'preQuery',
    ];
  }

  /**
   * Lets the document processors add their fields to the Solr documents.
   *
   * Solr needs to be aware of all group permissions so we can filter it in our
   * query builder depending of the user context.
   *
   * @param \Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent $event
   *   The event.
   */
  public function postCreateIndexDocuments(PostCreateIndexDocumentsEvent $event): void {
    $items = $event->getSearchApiItems();
    $processors = $this->processorCollector->getProcessors();

    /** @var \Solarium\QueryType\Update\Query\Document $document */
    foreach ($event->getSolariumDocuments() as $document) {
      $fields = $document->getFields();

      /** @var \Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface $processor */
      foreach ($processors as $processor) {
        if ($processor->supports($fields)) {
          $processor->process($document, $fields, $items);
          $processor->postProcess($document, $fields);
        }
      }
    }
  }

  /**
   * Adds the group access filters to the select query.
   *
   * @param \Drupal\search_api_solr\Event\PreQueryEvent $event
   *   The event.
   */
  public function preQuery(PreQueryEvent $event): void {
    $solarium_query = $event->getSolariumQuery();

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load('global');

    if (!$index instanceof IndexInterface) {
      return;
    }

    if (
      $index->isValidProcessor('group_content_access') &&
      GroupAccessContent::supportsIndex($index) &&
      $solarium_query instanceof SolrQuery
    ) {
      $index->getProcessor('group_content_access')
        ->preprocessSolrSearchQuery($solarium_query);
    }
  }

}
