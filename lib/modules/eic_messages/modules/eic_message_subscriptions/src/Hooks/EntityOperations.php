<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\MessageSubscriptionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of form hooks.
 *
 * @package Drupal\eic_message_subscriptions\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The message subscription helper service.
   *
   * @var \Drupal\eic_message_subscriptions\MessageSubscriptionHelper
   */
  protected $messageSubscriptionHelper;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\eic_message_subscriptions\MessageSubscriptionHelper $message_subscription_helper
   *   The message subscription helper service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   */
  public function __construct(
    MessageSubscriptionHelper $message_subscription_helper,
    CacheBackendInterface $cache_backend,
    QueueFactory $queue_factory
  ) {
    $this->messageSubscriptionHelper = $message_subscription_helper;
    $this->cacheBackend = $cache_backend;
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_message_subscriptions.helper'),
      $container->get('cache.default'),
      $container->get('queue')
    );
  }

  /**
   * Implements hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityInsert(EntityInterface $entity) {
    if (!$this->messageSubscriptionHelper->isMessageSubscriptionApplicable($entity)) {
      if ($entity->getEntityTypeId() === 'group_content') {
        $group_content_entity = $entity->getEntity();
        // Cache ID that identifies if an entity needs to trigger a
        // subscription notification.
        $cid = "eic_message_subscriptions:entity_notify:{$group_content_entity->getEntityTypeId()}:{$group_content_entity->id()}";
        // Deletes the cache.
        $this->cacheBackend->delete($cid);
      }
      return;
    }

    $message_subscription_queue = $this->queueFactory->get(CronOperations::MESSAGE_SUBSCRIPTIONS_QUEUE);

    // Initialize message subscription item to be added to the message
    // subscription queue. We need to do this otherwise the process of
    // sending the notification might take too long since it needs to get
    // the subscribed users before the notification is sent.
    $item = new \stdClass();

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Adds message subscription event name to the queue item.
        $item->message_subscription_event = MessageSubscriptionEvents::COMMENT_INSERT;
        // Adds the entity that is triggering the message subscription.
        $item->entity = $entity;
        // Adds message subscription item to the queue.
        $message_subscription_queue->createItem($item);
        break;

      case 'group_content':
        $node = $entity->getEntity();

        // Cache ID that identifies if an entity needs to trigger a
        // subscription notification.
        $cid = "eic_message_subscriptions:entity_notify:{$node->getEntityTypeId()}:{$node->id()}";

        // Grab the value from the cache.
        $send_subscription = $this->cacheBackend->get($cid);

        // If the group content node hasn't been added to the cache, it means
        // that the notification won't be sent out.
        if (!$send_subscription) {
          break;
        }

        // Adds message subscription event name to the queue item.
        $item->message_subscription_event = MessageSubscriptionEvents::GROUP_CONTENT_INSERT;
        // Adds the entity that is triggering the message subscription.
        $item->entity = $node;
        // Adds message subscription item to the queue.
        $message_subscription_queue->createItem($item);
        // Deletes the cache.
        $this->cacheBackend->delete($cid);
        break;

    }
  }

}
