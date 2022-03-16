<?php

namespace Drupal\eic_flags\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlaggingInterface;
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
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor|NULL $solr_document_processor
   *   The EIC Search Solr Document Processor.
   */
  public function setDocumentProcessor(?SolrDocumentProcessor $solr_document_processor) {
    $this->solrDocumentProcessor = $solr_document_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FlagEvents::ENTITY_FLAGGED] = ['onFlag', 50];
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

    // Some custom variables need to be updated in Solr, so we trigger the
    // re-index of the parent entity.
    if ($this->isReindexTargetedFlag($flagging)) {
      // Get the flagged entity to be updated.
      $parent_entity = $flagging->getFlaggable();
      Cache::invalidateTags($parent_entity->getCacheTags());
      $this->solrDocumentProcessor->reIndexEntities([$parent_entity]);
    }

  }

  /**
   * Checks if event relates to flag requiring a re-index of the host entity.
   *
   * @param Drupal\flag\FlaggingInterface $flagging
   *   The flagging object.
   *
   * @return bool
   *   Whether this flagging should re-index the host entity.
   */
  protected function isReindexTargetedFlag(FlaggingInterface $flagging) {
    $reindex_triggers = [
      FlagType::BOOKMARK_CONTENT,
      FlagType::HIGHLIGHT_CONTENT,
      FlagType::LIKE_CONTENT,
    ];
    return in_array($flagging->getFlagId(), $reindex_triggers);
  }

}
