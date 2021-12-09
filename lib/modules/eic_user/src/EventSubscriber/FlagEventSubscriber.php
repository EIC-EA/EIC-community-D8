<?php

namespace Drupal\eic_user\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\eic_flags\FlagType;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\taxonomy\TermInterface;
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
   * FlagEventSubscriber constructor.
   *
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User Helper service.
   */
  public function __construct(UserHelper $eic_user_helper) {
    $this->eicUserHelper = $eic_user_helper;
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

}
