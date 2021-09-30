<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\MessageSubscriptionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\eic_message_subscriptions\MessageSubscriptionHelper $message_subscription_helper
   *   The message subscription helper service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(
    MessageSubscriptionHelper $message_subscription_helper,
    EventDispatcherInterface $event_dispatcher,
    CacheBackendInterface $cache_backend
  ) {
    $this->messageSubscriptionHelper = $message_subscription_helper;
    $this->eventDispatcher = $event_dispatcher;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_message_subscriptions.helper'),
      $container->get('event_dispatcher'),
      $container->get('cache.default')
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

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Instantiate event.
        $event = new MessageSubscriptionEvent($entity);
        // Dispatch the event.
        $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::COMMENT_INSERT);
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

        // Instantiate event.
        $event = new MessageSubscriptionEvent($node);
        // Dispatch the event 'eic_message_subscriptions.group_content_insert'.
        $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::GROUP_CONTENT_INSERT);

        // Deletes the cache.
        $this->cacheBackend->delete($cid);
        break;

    }
  }

}
