<?php

namespace Drupal\eic_user\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\eic_flags\FlagType;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EIC User Flags subscriber.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FlagEvents::ENTITY_FLAGGED => ['invalidateFlaggedEntityCache', 50],
      FlagEvents::ENTITY_UNFLAGGED => ['invalidateUnflaggedEntityCache', 50],
    ];
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function invalidateFlaggedEntityCache(FlaggingEvent $event) {
    $flagging = $event->getFlagging();

    if ($flagging->getFlagId() !== FlagType::FOLLOW_USER) {
      return;
    }

    // Invalidate entity cache tags.
    Cache::invalidateTags($flagging->getFlaggable()->getCacheTagsToInvalidate());
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The flagging event.
   */
  public function invalidateUnflaggedEntityCache(UnflaggingEvent $event) {
    $flaggings = $event->getFlaggings();

    foreach ($flaggings as $flagging) {
      if ($flagging->getFlagId() !== FlagType::FOLLOW_USER) {
        continue;
      }

      // Invalidate entity cache tags.
      Cache::invalidateTags($flagging->getFlaggable()->getCacheTagsToInvalidate());
    }
  }

}
