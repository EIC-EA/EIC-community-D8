<?php

namespace Drupal\eic_share_content\EventSubscriber;

use Drupal\eic_share_content\Hooks\SolrDocumentDecorator;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Decorates the Solr documents for the share content feature.
 *
 * Replaces hook_search_api_solr_documents_alter(), removed in
 * search_api_solr 4.3.
 */
class SolrDocumentSubscriber implements EventSubscriberInterface {

  /**
   * The Solr document decorator.
   *
   * @var \Drupal\eic_share_content\Hooks\SolrDocumentDecorator
   */
  private SolrDocumentDecorator $decorator;

  /**
   * Constructs a new SolrDocumentSubscriber.
   *
   * @param \Drupal\eic_share_content\Hooks\SolrDocumentDecorator $decorator
   *   The Solr document decorator.
   */
  public function __construct(SolrDocumentDecorator $decorator) {
    $this->decorator = $decorator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENTS => 'postCreateIndexDocuments',
    ];
  }

  /**
   * Decorates the node documents.
   *
   * @param \Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent $event
   *   The event.
   */
  public function postCreateIndexDocuments(PostCreateIndexDocumentsEvent $event): void {
    $documents = $event->getSolariumDocuments();

    foreach ($documents as $key => $document) {
      $fields = $document->getFields();

      if (
        !isset($fields['ss_search_api_datasource'])
        || $fields['ss_search_api_datasource'] !== 'entity:node'
      ) {
        continue;
      }

      $documents[$key] = $this->decorator->decorate($document);
    }

    $event->setSolariumDocuments($documents);
  }

}
