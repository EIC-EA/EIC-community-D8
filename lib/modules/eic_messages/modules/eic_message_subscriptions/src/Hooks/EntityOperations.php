<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\MessageSubscriptionHelper;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\group\Entity\GroupInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

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
    EventDispatcherInterface $event_dispatcher,
    EntityTypeManagerInterface $entity_type_manager,
    ModerationInformationInterface $moderation_information,
    EventDispatcherInterface $dispatcher
  ) {
    $this->messageSubscriptionHelper = $message_subscription_helper;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_message_subscriptions.helper'),
      $container->get('queue'),
      $container->get('state'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $event
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function eventUpdate(GroupInterface $event) {
    if (!$this->moderationInformation->isModeratedEntity($event) || $event->bundle() !== 'event') {
      return;
    }

    if (!$event->isPublished()) {
      return;
    }

    $original = $event->original;
    $new_state = $event->get('moderation_state')->value;
    if ($original instanceof ContentEntityInterface
      && ($original->get('moderation_state')->value === $new_state
        && $original->get('moderation_state')->value !== GroupsModerationHelper::GROUP_DRAFT_STATE)) {
      return;
    }

    if (!$event->isPublished()
      || $new_state !== GroupsModerationHelper::GROUP_PUBLISHED_STATE
    ) {
      return;
    }

    $message_ids = $this->entityTypeManager->getStorage('message')
      ->getQuery()
      ->condition('template', MessageSubscriptionTypes::NEW_EVENT_PUBLISHED)
      ->condition('field_group_ref', $event->id())
      ->execute();
    if (!empty($message_ids)) {
      return;
    }

    // Means the group is transitioned to published and we don't have a message sent.
    $dispatched_event = new MessageSubscriptionEvent($event);
    $this->dispatcher->dispatch($dispatched_event, MessageSubscriptionEvents::GLOBAL_EVENT_INSERT);
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
