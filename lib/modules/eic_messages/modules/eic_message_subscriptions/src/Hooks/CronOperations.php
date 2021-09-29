<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class CronOperations.
 *
 * Implementations for hook_cron().
 */
class CronOperations implements ContainerInjectionInterface {

  /**
   * Group alias update queue name.
   */
  const MESSAGE_SUBSCRIPTIONS_QUEUE = 'eic_message_subscriptions_queue';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a CronOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue_factory,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('queue'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $this->processMessageSubscriptionQueue();
  }

  /**
   * Process message subscriptions queue.
   *
   * @todo In the future this method should be called by ultimate cron module.
   */
  protected function processMessageSubscriptionQueue() {
    $message_subscriptions_queue = $this->queueFactory->get(self::MESSAGE_SUBSCRIPTIONS_QUEUE);

    while ($item = $message_subscriptions_queue->claimItem()) {
      try {

        // If the message subscription event name is not in the item, we skip
        // this item.
        if (empty($item->data->message_subscription_event)) {
          $message_subscriptions_queue->deleteItem($item);
          continue;
        }

        $message_subscription_event = $item->data->message_subscription_event;

        // If the message subscription event is not a string, we skip this
        // item.
        if (!is_string($message_subscription_event)) {
          $message_subscriptions_queue->deleteItem($item);
          continue;
        }

        // If the message subscription event is not defined in
        // MessageSubscriptionEvents, we skip this item.
        if (!in_array($message_subscription_event, MessageSubscriptionEvents::getEventsArray())) {
          $message_subscriptions_queue->deleteItem($item);
          continue;
        }

        // If the entity object is not in the item, we skip this item.
        if (empty($item->data->entity)) {
          $message_subscriptions_queue->deleteItem($item);
          continue;
        }

        $entity = $item->data->entity;

        // Entity needs to be an instance of ContentEntityInterface otherwise
        // we can skip this item.
        if (!($entity instanceof ContentEntityInterface)) {
          $message_subscriptions_queue->deleteItem($item);
          continue;
        }

        // Instantiate MessageSubscriptionEvent.
        $event = new MessageSubscriptionEvent($entity);
        // Dispatch the event.
        $this->eventDispatcher->dispatch($event, $message_subscription_event);
        // Delete item from the queue.
        $message_subscriptions_queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $message_subscriptions_queue->releaseItem($item);
        break;
      }
    }
  }

}
