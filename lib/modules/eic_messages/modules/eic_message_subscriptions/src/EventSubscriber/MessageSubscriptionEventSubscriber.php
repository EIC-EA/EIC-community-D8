<?php

namespace Drupal\eic_message_subscriptions\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\eic_messages\Service\CommentMessageCreator;
use Drupal\eic_messages\Service\GroupContentMessageCreator;
use Drupal\eic_messages\Service\NodeMessageCreator;
use Drupal\group\Entity\GroupContent;
use Drupal\message\MessageInterface;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EIC Message Notifications event subscriber.
 */
class MessageSubscriptionEventSubscriber implements EventSubscriberInterface {

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $notifier;

  /**
   * The Comment Message Creator service.
   *
   * @var \Drupal\eic_messages\Service\CommentMessageCreator
   */
  protected $commentMessageCreator;

  /**
   * The GroupContent Message Creator service.
   *
   * @var \Drupal\eic_messages\Service\GroupContentMessageCreator
   */
  protected $groupContentMessageCreator;

  /**
   * The Node Message Creator service.
   *
   * @var \Drupal\eic_messages\Service\NodeMessageCreator
   */
  protected $nodeMessageCreator;

  /**
   * The EIC Flag Helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $eicFlagsHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\message_notify\MessageNotifier $notifier
   *   The message notifier.
   * @param \Drupal\eic_messages\Service\CommentMessageCreator $comment_message_creator
   *   The Comment Message Creator service.
   * @param \Drupal\eic_messages\Service\GroupContentMessageCreator $group_content_message_creator
   *   The GroupContent Message Creator service.
   * @param \Drupal\eic_messages\Service\NodeMessageCreator $node_message_creator
   *   The Node Message Creator service.
   * @param \Drupal\eic_flags\FlagHelper $eic_flags_helper
   *   The EIC Flag Helper service.
   */
  public function __construct(
    MessageNotifier $notifier,
    CommentMessageCreator $comment_message_creator,
    GroupContentMessageCreator $group_content_message_creator,
    NodeMessageCreator $node_message_creator,
    FlagHelper $eic_flags_helper
  ) {
    $this->notifier = $notifier;
    $this->commentMessageCreator = $comment_message_creator;
    $this->groupContentMessageCreator = $group_content_message_creator;
    $this->nodeMessageCreator = $node_message_creator;
    $this->eicFlagsHelper = $eic_flags_helper;
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

    $subscribed_users = $this->getSubscribedUsers($entity);

    // Set the subscription operation.
    $operation = SubscriptionOperationTypes::NEW_ENTITY;

    // If comment is a reply we set the operation to 'comment_reply'.
    if ($entity->hasParentComment()) {
      $operation = SubscriptionOperationTypes::COMMENT_REPLY;
    }

    $message = $this->commentMessageCreator->createCommentSubscription(
      $entity,
      $operation
    );

    // Send message notifications.
    $this->notifyUsers($message, $subscribed_users);
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

    // When a group content is created we need grab the users subscribed to the
    // group instead of the node.
    $subscribed_users = $this->getSubscribedUsers($group);

    $message = $this->groupContentMessageCreator->createGroupContentSubscription(
      $entity,
      $group,
      $operation
    );

    // Send message notifications.
    $this->notifyUsers($message, $subscribed_users);
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

    // Get the users subscribed to the node.
    $subscribed_users = $this->getSubscribedUsers($entity);

    $group_content = reset($group_contents);

    $group = $group_content->getGroup();

    $message = $this->groupContentMessageCreator->createGroupContentSubscription(
      $entity,
      $group,
      $operation
    );

    // Send message notifications.
    $this->notifyUsers($message, $subscribed_users);
  }

  /**
   * Node created event handler.
   *
   * @param \Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent $event
   *   The MessageSubscription event.
   */
  public function nodeCreated(MessageSubscriptionEvent $event) {
    $entity = $event->getEntity();

    $node_topics = $entity->get('field_vocab_topics')->referencedEntities();

    if (empty($node_topics)) {
      return;
    }

    $subscribed_users = [];

    foreach ($node_topics as $topic) {
      $subscribed_users = $this->getSubscribedUsers($topic);

      foreach (array_keys($subscribed_users) as $uid) {
        // If this user is already in the array of subscribed users we can skip
        // it.
        if (isset($subscribed_users[$uid])) {
          continue;
        }
      }
    }

    // Set the subscription operation.
    $operation = SubscriptionOperationTypes::NEW_ENTITY;

    $message = $this->nodeMessageCreator->createTermsOfInterestNodeSubscription(
      $entity,
      $operation
    );

    // Send message notifications.
    $this->notifyUsers($message, $subscribed_users);
  }

  /**
   * Get users who subscribed to a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The subscribed entity.
   *
   * @return array
   *   An array of users who have flagged the entity.
   */
  private function getSubscribedUsers(EntityInterface $entity) {
    $users = [];

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        $commented_entity = $entity->getCommentedEntity();
        // Get users who are following the commented entity.
        $users = $this->eicFlagsHelper->getFlaggingUsersByFlagIds($commented_entity, ['follow_content']);
        break;

      case 'node':
        // Get users who are following the node.
        $users = $this->eicFlagsHelper->getFlaggingUsersByFlagIds($entity, ['follow_content']);
        break;

      case 'group':
        // Get users who are following the group.
        $users = $this->eicFlagsHelper->getFlaggingUsersByFlagIds($entity, ['follow_group']);
        break;

      case 'taxonomy_term':
        // Get users who are following the group.
        $users = $this->eicFlagsHelper->getFlaggingUsersByFlagIds($entity, ['follow_taxonomy_term']);
        break;

    }

    return $users;
  }

  /**
   * Send message subscription notification to users.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message entity.
   * @param array $users
   *   Array of user entities to notify.
   */
  private function notifyUsers(MessageInterface $message, array $users) {
    foreach ($users as $user) {
      $message->setOwnerId($user->id());
      // @todo Send message to a queue to be processed later by cron.
      $this->notifier->send($message);
    }
  }

}
