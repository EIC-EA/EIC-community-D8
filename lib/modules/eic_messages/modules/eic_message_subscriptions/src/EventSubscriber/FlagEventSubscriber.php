<?php

namespace Drupal\eic_message_subscriptions\EventSubscriber;

use Drupal\eic_flags\FlagType;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to trigger message subscriptions on flag events.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * FlagEventSubscriber constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FlagEvents::ENTITY_FLAGGED => ['onFlag', 50],
    ];
  }

  /**
   * React to flagging event.
   *
   * Queues up a message subscription item to be processed later by cron, so
   * that subscribed users can receive a notification.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    // Gets the flagging entity associated with the event.
    $flagging = $event->getFlagging();

    // Gets the flag entity from flagging.
    $flag = $flagging->getFlag();

    if (!$this->isApplicable($flag)) {
      return;
    }

    // Gets the flagged entity.
    $flagged_entity = $flagging->getFlaggable();

    // If entity is not publish we don't need to notify users, so we can exit.
    if (!$flagged_entity->isPublished()) {
      return;
    }

    $message_subscription_event = FALSE;

    switch ($flag->id()) {
      case FlagType::RECOMMEND_NODE:
        // Sets message subscription event name.
        $message_subscription_event = MessageSubscriptionEvents::CONTENT_RECOMMENDED;
        break;

    }

    if (!$message_subscription_event) {
      return;
    }

    // Instantiate MessageSubscriptionEvent.
    $event = new MessageSubscriptionEvent($flagging);
    // Dispatch the event to trigger message subscription notifications.
    $this->eventDispatcher->dispatch($event, $message_subscription_event);
  }

  /**
   * Checks if a flag can trigger message subscriptions.
   *
   * @return bool
   *   TRUE if the flag can trigger message subscriptions.
   */
  public function isApplicable(FlagInterface $flag) {
    if (eic_migrate_is_migration_running()) {
      FALSE;
    }

    $allowed_flag_types = self::getAllowedMessageSubscriptionFlagTypes();
    return in_array($flag->id(), $allowed_flag_types);
  }

  /**
   * Gets flag types that can trigger message subscriptions.
   *
   * @return array
   *   Array of allowed flag types.
   */
  public static function getAllowedMessageSubscriptionFlagTypes() {
    return [
      FlagType::RECOMMEND_NODE,
    ];
  }

}
