<?php

namespace Drupal\eic_message_subscriptions\EventSubscriber;

use Drupal\Core\Queue\QueueFactory;
use Drupal\eic_flags\FlagType;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\Hooks\CronOperations;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to trigger message subscriptions on flag events.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * FlagEventSubscriber constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->queueFactory = $queue_factory;
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

    $message_subscription_queue = $this->queueFactory->get(CronOperations::MESSAGE_SUBSCRIPTIONS_QUEUE);

    // Initialize message subscription item to be added to the message
    // subscription queue.
    $item = new \stdClass();

    switch ($flag->id()) {
      case FlagType::RECOMMEND:
        // Adds message subscription event name to the queue item.
        $item->message_subscription_event = MessageSubscriptionEvents::CONTENT_RECOMMENDED;
        // Adds the flagging that is associated with the message subscription.
        $item->entity = $flagging;
        break;

    }

    if (!$item->message_subscription_event) {
      return;
    }

    // Adds message subscription item to the queue.
    $message_subscription_queue->createItem($item);
  }

  /**
   * Checks if a flag can trigger message subscriptions.
   *
   * @return bool
   *   TRUE if the flag can trigger message subscriptions.
   */
  public function isApplicable(FlagInterface $flag) {
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
      FlagType::RECOMMEND,
    ];
  }

}
