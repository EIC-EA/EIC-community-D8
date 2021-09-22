<?php

namespace Drupal\eic_flags\EventSubscriber;

use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EIC Flags subscriber.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  /**
   * The EIC Search Solr Document Processor.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * FlagEventSubscriber constructor.
   *
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor $solr_document_processor
   *   The EIC Search Solr Document Processor.
   */
  public function __construct(SolrDocumentProcessor $solr_document_processor) {
    $this->solrDocumentProcessor = $solr_document_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FlagEvents::ENTITY_FLAGGED] = ['onFlag', 50];
    $events[FlagEvents::ENTITY_UNFLAGGED] = ['onUnflag', 50];
    return $events;
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    $flagging = $event->getFlagging();

    $reindex_triggers = [
      FlagType::LIKE_CONTENT,
    ];

    // Some custom variables need to be updated in Solr, so we trigger the
    // re-index of the parent entity.
    if (in_array($flagging->getFlagId(), $reindex_triggers)) {
      // Get the flagged entity to be updated.
      $parent_entity = $flagging->getFlaggable();
      $this->solrDocumentProcessor->reIndexEntities([$parent_entity]);
    }
  }

  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function onUnflag(UnflaggingEvent $event) {

  }

}
