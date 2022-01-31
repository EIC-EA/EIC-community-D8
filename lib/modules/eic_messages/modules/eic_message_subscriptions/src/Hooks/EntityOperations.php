<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
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
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\eic_message_subscriptions\MessageSubscriptionHelper $message_subscription_helper
   *   The message subscription helper service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    MessageSubscriptionHelper $message_subscription_helper,
    QueueFactory $queue_factory,
    StateInterface $state,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->messageSubscriptionHelper = $message_subscription_helper;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_message_subscriptions.helper'),
      $container->get('queue'),
      $container->get('state'),
      $container->get('event_dispatcher')
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
        // State cache ID that identifies a new group content creation.
        $state_key = MessageSubscriptionHelper::GROUP_CONTENT_CREATED_STATE_KEY;
        // Increments entity type and entity ID to the state cache ID.
        $state_key .= ":{$group_content_entity->getEntityTypeId()}:{$group_content_entity->id()}";
        // Deletes the item from the state cache.
        $this->state->delete($state_key);
      }
      return;
    }

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Instantiate MessageSubscriptionEvent.
        $event = new MessageSubscriptionEvent($entity);
        // Dispatch the event to trigger message subscription notifications.
        $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::COMMENT_INSERT);
        break;

      case 'group_content':
        $node = $entity->getEntity();

        // State cache ID that represents a new group content creation.
        $state_key = MessageSubscriptionHelper::GROUP_CONTENT_CREATED_STATE_KEY;
        // Increments entity type and entity ID to the state cache ID.
        $state_key .= ":{$node->getEntityTypeId()}:{$node->id()}";

        // Grab the value from the cache.
        $send_subscription = $this->state->get($state_key);

        // If the group content node hasn't been added to the cache, it means
        // that the notification won't be sent out.
        if (!$send_subscription) {
          break;
        }

        // Instantiate MessageSubscriptionEvent.
        $event = new MessageSubscriptionEvent($node);
        // Dispatch the event to trigger message subscription notifications.
        $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::GROUP_CONTENT_INSERT);
        // Deletes the item from the state cache.
        $this->state->delete($state_key);
        break;

    }
  }

}
