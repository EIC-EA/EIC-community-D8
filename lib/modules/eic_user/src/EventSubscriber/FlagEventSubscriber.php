<?php

namespace Drupal\eic_user\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EIC User Flags subscriber.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  /**
   * The EIC User Helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  private $eicUserHelper;

  /**
   * The solr document processor service.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor $solrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $em
   */
  private $em;

  /**
   * FlagEventSubscriber constructor.
   *
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User Helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $em ;
   *   The entity type manager.
   */
  public function __construct(
    UserHelper $eic_user_helper,
    EntityTypeManagerInterface $em
  ) {
    $this->eicUserHelper = $eic_user_helper;
    $this->em = $em;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FlagEvents::ENTITY_FLAGGED => ['onFlag', 50],
      FlagEvents::ENTITY_UNFLAGGED => ['onUnFlag', 50],
    ];
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $this->flagUnFlag($flagging, FlagEvents::ENTITY_FLAGGED);
    $this->handleFollowContentReindex($flagging);
  }

  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The flagging event.
   */
  public function onUnFlag(UnflaggingEvent $event) {
    $flaggings = $event->getFlaggings();
    $this->flagUnFlag(reset($flaggings), FlagEvents::ENTITY_UNFLAGGED);
    $this->handleFollowContentReindex(reset($flaggings));
  }

  /**
   * Handles flag/unflag events.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging entity.
   * @param string $flag_event
   *   The flag event. Either FlagEvents::ENTITY_FLAGGED or
   *   FlagEvents::ENTITY_UNFLAGGED.
   */
  public function flagUnFlag(FlaggingInterface $flagging, $flag_event) {
    $invalidate_cache = FALSE;
    switch ($flagging->getFlagId()) {
      case FlagType::FOLLOW_USER:
        $invalidate_cache = TRUE;
        break;

      case FlagType::FOLLOW_TAXONOMY_TERM:
        /** @var \Drupal\taxonomy\TermInterface $taxonomy_term */
        $taxonomy_term = $flagging->getFlaggable();

        if ($taxonomy_term->bundle() !== Topics::TERM_VOCABULARY_TOPICS_ID) {
          break;
        }

        $user_profile = $this->eicUserHelper->getUserMemberProfile($flagging->getOwner());

        if (!$user_profile) {
          break;
        }

        $this->updateUserProfileTopics($user_profile, $taxonomy_term, $flag_event);
        $invalidate_cache = TRUE;
        break;

    }

    if ($invalidate_cache) {
      $this->invalidateFlaggedEntityCacheTags($flagging);
    }
  }

  /**
   * Invalidate flagged entity cache tags.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging entity.
   */
  private function invalidateFlaggedEntityCacheTags(FlaggingInterface $flagging) {
    Cache::invalidateTags($flagging->getFlaggable()->getCacheTagsToInvalidate());
  }

  /**
   * Updates user profile topics when flag/unflag a topic.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile object.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The topic term to add/remove from user profile.
   * @param string $flag_event
   *   The flag event. Either FlagEvents::ENTITY_FLAGGED or
   *   FlagEvents::ENTITY_UNFLAGGED.
   */
  private function updateUserProfileTopics(ProfileInterface $profile, TermInterface $taxonomy_term, $flag_event) {
    $vocab_field_name = Topics::TERM_TOPICS_ID_FIELD;

    if (!$profile->hasField($vocab_field_name)) {
      return;
    }

    $topics = [];
    $user_topics = $profile->get($vocab_field_name)->referencedEntities();
    foreach ($user_topics as $topic) {
      $topics[$topic->id()] = $topic->id();
    }

    $needs_update = FALSE;
    switch ($flag_event) {
      case FlagEvents::ENTITY_FLAGGED:
        if (isset($topics[$taxonomy_term->id()])) {
          break;
        }
        $topics[$taxonomy_term->id()] = $taxonomy_term->id();
        $needs_update = TRUE;
        break;

      case FlagEvents::ENTITY_UNFLAGGED:
        if (!isset($topics[$taxonomy_term->id()])) {
          break;
        }
        unset($topics[$taxonomy_term->id()]);
        $needs_update = TRUE;
        break;

    }

    if ($needs_update) {
      $profile->$vocab_field_name = $topics;
      $profile->save();
    }
  }

  /**
   * Reindex messages link to the flag entity.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  private function handleFollowContentReindex(FlaggingInterface $flagging): void {
    $entity = $flagging->getFlaggable();

    switch ($flagging->getFlagId()) {
      case 'follow_content':
        if (!$entity instanceof NodeInterface) {
          break;
        }

        $messages = $this->em->getStorage('message')->loadByProperties([
          'field_referenced_node' => $entity->id(),
        ]);

        $this->solrDocumentProcessor->reIndexEntities($messages);
        break;
      case 'follow_user':
        if (!$entity instanceof UserInterface) {
          break;
        }

        $messages = $this->em->getStorage('message')->loadByProperties([
          'uid' => $entity->id(),
        ]);

        $this->solrDocumentProcessor->reIndexEntities($messages);
        break;
      case 'follow_group':
        if (!$entity instanceof GroupInterface) {
          break;
        }

        $messages = $this->em->getStorage('message')->loadByProperties([
          'field_group_ref' => $entity->id(),
        ]);

        $this->solrDocumentProcessor->reIndexEntities($messages);
        break;
      case 'follow_taxonomy_term':
        if (!$entity instanceof TermInterface) {
          break;
        }

        $groups = $this->em->getStorage('group')->loadByProperties([
          'field_vocab_topics' => $entity->id(),
        ]);

        $message_groups = $this->em->getStorage('message')->loadByProperties([
          'field_group_ref' => array_map(function (GroupInterface $group) {
            return $group->id();
          }, $groups),
        ]);

        $nodes = $this->em->getStorage('node')->loadByProperties([
          'field_vocab_topics' => $entity->id(),
        ]);

        $message_nodes = $this->em->getStorage('message')->loadByProperties([
          'field_referenced_node' => array_map(function (NodeInterface $node) {
            return $node->id();
          }, $nodes),
        ]);

        $this->solrDocumentProcessor->reIndexEntities($message_groups);
        $this->solrDocumentProcessor->reIndexEntities($message_nodes);
        break;
    }
  }

  /**
   * Setter method to inject the SolrDocumentProcessor.
   *
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor|null $solr_document_processor
   *   The EIC Search Solr Document Processor.
   */
  public function setDocumentProcessor(?SolrDocumentProcessor $solr_document_processor) {
    $this->solrDocumentProcessor = $solr_document_processor;
  }

}
