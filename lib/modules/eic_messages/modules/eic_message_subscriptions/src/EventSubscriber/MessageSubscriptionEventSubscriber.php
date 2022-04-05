<?php

namespace Drupal\eic_message_subscriptions\EventSubscriber;

use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\Service\SubscriptionMessageCreator;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\eic_messages\Util\QueuedMessageChecker;
use Drupal\eic_migrate\Commands\MigrateToolsOverrideCommands;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\message_subscribe\SubscribersInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EIC Message Notifications event subscriber.
 *
 * @todo This class can be improved and instead of having a method for each
 * event, we could probably add the event type as a property of the Event
 * object (see MessageSubscriptionEvent) and use it to build our set of
 * conditions.
 */
class MessageSubscriptionEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\eic_message_subscriptions\Service\SubscriptionMessageCreator
   */
  private $messageCreator;

  /**
   * The message subscribers service.
   *
   * @var \Drupal\message_subscribe\SubscribersInterface
   */
  private $messageSubscribersService;

  /**
   * @var \Drupal\eic_messages\Util\QueuedMessageChecker
   */
  private $queuedMessageChecker;

  /**
   * @param \Drupal\eic_message_subscriptions\Service\SubscriptionMessageCreator $message_creator
   * @param \Drupal\message_subscribe\SubscribersInterface $message_subscribers_service
   * @param \Drupal\eic_messages\Util\QueuedMessageChecker $queued_message_checker
   */
  public function __construct(
    SubscriptionMessageCreator $message_creator,
    SubscribersInterface $message_subscribers_service,
    QueuedMessageChecker $queued_message_checker
  ) {
    $this->messageCreator = $message_creator;
    $this->messageSubscribersService = $message_subscribers_service;
    $this->queuedMessageChecker = $queued_message_checker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MessageSubscriptionEvents::COMMENT_INSERT => ['commentCreated'],
      MessageSubscriptionEvents::GROUP_CONTENT_INSERT => ['groupContentCreated'],
      MessageSubscriptionEvents::GROUP_CONTENT_UPDATE => ['groupContentUpdated'],
      MessageSubscriptionEvents::NODE_INSERT => ['nodeCreated'],
      MessageSubscriptionEvents::CONTENT_RECOMMENDED => ['contentRecommended'],
    ];
  }

  /**
   * Comment created event handler.
   *
   * @param \Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent $event
   *   The MessageSubscription event.
   */
  public function commentCreated(MessageSubscriptionEvent $event) {
    $entity = $event->getEntity();
    // Set the subscription operation.
    $operation = SubscriptionOperationTypes::NEW_ENTITY;
    // If comment is a reply we set the operation to 'comment_reply'.
    if ($entity->hasParentComment()) {
      $operation = SubscriptionOperationTypes::COMMENT_REPLY;
    }

    $message = $this->messageCreator->createCommentSubscription(
      $entity,
      $operation
    );

    // Adds the node to the context so that message_subscribe module can grab
    // all users that are subscribed to the node.
    $context = [
      'node' => [
        $entity->getCommentedEntity()->id(),
      ],
    ];

    // Send message notifications.
    $this->messageSubscribersService->sendMessage($entity, $message, [], [], $context);
  }

  /**
   * Group content created event handler.
   *
   * @param \Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent $event
   *   The MessageSubscription event.
   */
  public function groupContentCreated(MessageSubscriptionEvent $event) {
    $entity = $event->getEntity();
    $group_contents = GroupContent::loadByEntity($entity);
    if (empty($group_contents)) {
      return;
    }

    // Default operation when new entity is added.
    $operation = SubscriptionOperationTypes::NEW_ENTITY;
    $group_content = reset($group_contents);
    $group = $group_content->getGroup();
    $message = $this->messageCreator->createGroupContentSubscription(
      $entity,
      $group,
      $operation
    );

    // Check if we should create/send the message.
    if (!$this->queuedMessageChecker->shouldCreateNewMessage($message)) {
      return;
    }

    // Adds the group to the context so that message_subscribe module can grab
    // all users that are subscribed to the group.
    $context = [
      'group' => [
        $group->id(),
      ],
    ];

    // Send message notifications.
    $this->messageSubscribersService->sendMessage($entity, $message, [], [], $context);
  }

  /**
   * Node update event handler.
   *
   * @param \Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent $event
   *   The MessageSubscription event.
   */
  public function groupContentUpdated(MessageSubscriptionEvent $event) {
    $entity = $event->getEntity();
    $group_contents = GroupContent::loadByEntity($entity);
    if (empty($group_contents)) {
      return;
    }

    // Set the subscription operation.
    $operation = SubscriptionOperationTypes::UPDATED_ENTITY;
    $group_content = reset($group_contents);
    $group = $group_content->getGroup();
    $message = $this->messageCreator->createGroupContentSubscription(
      $entity,
      $group,
      $operation
    );

    // Check if we should create/send the message.
    if (!$this->queuedMessageChecker->shouldCreateNewMessage($message)) {
      return;
    }

    // Adds the node to the context so that message_subscribe module can grab
    // all users that are subscribed to node.
    $context = [
      'node' => [
        $entity->id(),
      ],
    ];

    // Send message notifications.
    $this->messageSubscribersService->sendMessage($entity, $message, [], [], $context);
  }

  /**
   * Node created event handler.
   *
   * @param \Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent $event
   *   The MessageSubscription event.
   */
  public function nodeCreated(MessageSubscriptionEvent $event) {
    $entity = $event->getEntity();
    // Set the subscription operation.
    $operation = SubscriptionOperationTypes::NEW_ENTITY;
    $message = $this->messageCreator->createTermsOfInterestNodeSubscription(
      $entity,
      $operation
    );

    $context = [];
    $node_topics = $entity->get('field_vocab_topics')->referencedEntities();
    // Adds each topic to the context so that message_subscribe module can
    // grab all users that are subscribed to each topic.
    foreach ($node_topics as $topic_term) {
      $context['taxonomy_term'][] = $topic_term->id();
    }

    // Send message notifications.
    $this->messageSubscribersService->sendMessage($entity, $message, [], [], $context);
  }

  /**
   * Content recommended event handler.
   *
   * @param \Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent $event
   *   The MessageSubscription event.
   */
  public function contentRecommended(MessageSubscriptionEvent $event) {
    $flagging = $event->getEntity();
    if (!($flagging instanceof FlaggingInterface)) {
      return;
    }

    $message = $this->messageCreator->createContentRecommendedSubscription(
      $flagging
    );

    // Check if we should create/send the message.
    if (!$this->queuedMessageChecker->shouldCreateNewMessage($message)) {
      return;
    }

    $flagged_entity = $flagging->getFlaggable();

    // Adds the flagged node to the context so that message_subscribe module
    // can grab all users that are subscribed to node.
    $context = [
      'node' => [
        $flagged_entity->id(),
      ],
    ];

    // Send message notifications.
    $this->messageSubscribersService->sendMessage($flagged_entity, $message, [], [], $context);
  }

}
