<?php

namespace Drupal\eic_flags\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\eic_flags\FlagType;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupInterface;
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
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  private $groupHelper;

  /**
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $group_helper
   */
  public function __construct(EICGroupsHelperInterface $group_helper) {
    $this->groupHelper = $group_helper;
  }

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
    $events[FlagEvents::ENTITY_UNFLAGGED] = ['flagUnFlag', 50];
    return $events;
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();

    $entity = $event->getFlagging()->getFlaggable();
    $group = $this->groupHelper->getGroupFromContent($entity);
    $extra_resync = [];

    if ($group instanceof GroupInterface && $flagging->getFlagId() === FlagType::RECOMMEND_GROUP) {
      /** @var \Drupal\eic_search\Service\SolrDocumentProcessor $solr_helper */
      // Reindex group parent to have correct most active score.
      $extra_resync[] = $group;
    }

    $this->invalidateDependencies($flagging, $extra_resync);
  }

  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  public function flagUnFlag(UnflaggingEvent $event) {
    $flaggings = $event->getFlaggings();
    $flagging = reset($flaggings);
    $group = $this->groupHelper->getGroupFromContent($flagging->getFlaggable());
    $extra_resync = [];

    if ($group instanceof GroupInterface && $flagging->getFlagId() === FlagType::RECOMMEND_GROUP) {
      /** @var \Drupal\eic_search\Service\SolrDocumentProcessor $solr_helper */
      // Reindex group parent to have correct most active score.
      $extra_resync[] = $group;
    }

    $this->invalidateDependencies($flagging, $extra_resync);
  }

  /**
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\Core\Entity\EntityInterface[] $extra_resync
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  private function invalidateDependencies(FlaggingInterface $flagging, array $extra_resync = []) {
    // Some custom variables need to be updated in Solr, so we trigger the
    // re-index of the parent entity.
    if ($this->isReindexTargetedFlag($flagging)) {
      // Get the flagged entity to be updated.
      $parent_entity = $flagging->getFlaggable();
      Cache::invalidateTags($parent_entity->getCacheTags());
      $extra_resync[] = $parent_entity;
    }

    $this->solrDocumentProcessor->reIndexEntities($extra_resync);
  }

  /**
   * Checks if event relates to flag requiring a re-index of the host entity.
   *
   * @param FlaggingInterface $flagging
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
