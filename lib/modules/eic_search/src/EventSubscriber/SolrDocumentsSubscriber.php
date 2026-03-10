<?php

declare(strict_types=1);

namespace Drupal\eic_search\EventSubscriber;

use Drupal\eic_search\Collector\DocumentProcessorCollector;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for Solr document processing.
 *
 * Replaces the deprecated hook_search_api_solr_documents_alter() hook
 * with an event-based approach for search_api_solr 4.3+.
 */
class SolrDocumentsSubscriber implements EventSubscriberInterface {

  /**
   * The document processor collector.
   *
   * @var \Drupal\eic_search\Collector\DocumentProcessorCollector
   */
  private DocumentProcessorCollector $processorCollector;

  /**
   * Constructs a new SolrDocumentsSubscriber.
   *
   * @param \Drupal\eic_search\Collector\DocumentProcessorCollector $processor_collector
   *   The document processor collector.
   */
  public function __construct(DocumentProcessorCollector $processor_collector) {
    $this->processorCollector = $processor_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENTS => ['onPostCreateIndexDocuments'],
    ];
  }

  /**
   * Handles the PostCreateIndexDocumentsEvent.
   *
   * Runs all document processors on the Solr documents before they are
   * sent to the Solr server for indexing.
   *
   * @param \Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent $event
   *   The event containing the Solr documents.
   */
  public function onPostCreateIndexDocuments(PostCreateIndexDocumentsEvent $event): void {
    $documents = $event->getSolariumDocuments();
    $items = $event->getSearchApiItems();
    $processors = $this->processorCollector->getProcessors();

    foreach ($documents as $document) {
      $fields = $document->getFields();

      foreach ($processors as $processor) {
        if ($processor->supports($fields)) {
          $processor->process($document, $fields, $items);
          $processor->postProcess($document, $fields);
        }
      }
    }

    $event->setSolariumDocuments($documents);
  }

}
