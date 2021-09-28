<?php

namespace Drupal\eic_message_subscriptions\Hooks;

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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\eic_message_subscriptions\MessageSubscriptionHelper $message_subscription_helper
   *   The message subscription helper service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    MessageSubscriptionHelper $message_subscription_helper,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->messageSubscriptionHelper = $message_subscription_helper;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_message_subscriptions.helper'),
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
      return;
    }

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Instantiate event.
        $event = new MessageSubscriptionEvent($entity);
        // Dispatch the event.
        $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::COMMENT_INSERT);
        break;

    }
  }

}
