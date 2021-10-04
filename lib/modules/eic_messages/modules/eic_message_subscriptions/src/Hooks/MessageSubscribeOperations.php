<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\group\Entity\GroupContent;
use Drupal\message\MessageInterface;
use Drupal\message_subscribe\Subscribers\DeliveryCandidate;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MessageSubscribeOperations.
 *
 * Implementations for form hooks.
 *
 * @package Drupal\eic_message_subscriptions\Hooks
 */
class MessageSubscribeOperations implements ContainerInjectionInterface {

  /**
   * The EIC Flag Helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $eicFlagsHelper;

  /**
   * Constructs a new MessageSubscribeOperations object.
   *
   * @param \Drupal\eic_flags\FlagHelper $eic_flags_helper
   *   The EIC Flag Helper service.
   */
  public function __construct(FlagHelper $eic_flags_helper) {
    $this->eicFlagsHelper = $eic_flags_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_flags.helper')
    );
  }

  /**
   * Allow modules to add user IDs that need to be notified.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message object.
   * @param array $subscribe_options
   *   Subscription options as defined by
   *   \Drupal\message\MessageInterface::sendMessage().
   * @param array $context
   *   Array keyed with the entity type and array of entity IDs as the
   *   value. According to this context this function will retrieve the
   *   related subscribers.
   *
   * @return \Drupal\message_subscribe\Subscribers\DeliveryCandidateInterface[]
   *   An array, keyed by recipeint user ID, of delivery candidate objects.
   */
  public function messageSubscribeGetSubscribers(
    MessageInterface $message,
    array $subscribe_options = [],
    array $context = []
  ) {
    $subscribers = [];
    $executing_user = FALSE;

    switch ($message->getTemplate()->id()) {
      case MessageSubscriptionTypes::NEW_COMMENT:
      case MessageSubscriptionTypes::NEW_COMMENT_REPLY:
        $comment = $message->get('field_referenced_comment')->entity;
        $executing_user = $comment->getOwner();
        $subscribers = $this->getSubscribedUsers($comment);
        break;

      case MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED:
        $node = $message->get('field_referenced_node')->entity;
        $group_contents = GroupContent::loadByEntity($node);

        if (empty($group_contents)) {
          break;
        }

        $group_content = reset($group_contents);

        $group = $group_content->getGroup();

        // When a group content is created we need grab the users subscribed to
        // the group instead of the node.
        $subscribers = $this->getSubscribedUsers($group);
        break;

      case MessageSubscriptionTypes::GROUP_CONTENT_UPDATED:
        $node = $message->get('field_referenced_node')->entity;
        $subscribers = $this->getSubscribedUsers($node);
        break;

      case MessageSubscriptionTypes::NODE_PUBLISHED:
        $node = $message->get('field_node_ref')->entity;
        $node_topics = $node->get('field_vocab_topics')->referencedEntities();

        if (empty($node_topics)) {
          return $subscribers;
        }

        foreach ($node_topics as $topic) {
          $subscribed_users = $this->getSubscribedUsers($topic);

          foreach ($subscribed_users as $uid => $user) {
            // If this user is already in the array of subscribed users we can
            // skip it.
            if (isset($subscribers[$uid])) {
              continue;
            }

            $subscribers[$uid] = $user;
          }
        }
        break;

      case MessageSubscriptionTypes::CONTENT_RECOMMENDED:
        $node = $message->get('field_referenced_node')->entity;
        $subscribers = $this->getSubscribedUsers($node);
        break;

    }

    // Gets the executing user if exists.
    if ($message->hasField('field_event_executing_user')) {
      $executing_user = $message->get('field_event_executing_user')->entity;
    }

    // We don't want to notify the user who triggered the subscription.
    // Therefore, we remove it from the array of subscribed users.
    if ($executing_user && $executing_user instanceof UserInterface) {
      if (isset($subscribers[$executing_user->id()])) {
        unset($subscribers[$executing_user->id()]);
      }
    }

    return $this->prepareDeliveryCandidates(array_keys($subscribers));
  }

  /**
   * Prepares array of delivery candidates to send the message subscription.
   *
   * @param array $uids
   *   Array of user IDs.
   *
   * @return \Drupal\message_subscribe\Subscribers\DeliveryCandidate[]
   *   An array, keyed by recipeint user ID, of delivery candidate objects.
   */
  protected function prepareDeliveryCandidates(array $uids = []) {
    $candidates = [];

    foreach ($uids as $uid) {
      $candidates[$uid] = new DeliveryCandidate([], ['email'], $uid);
    }

    return $candidates;
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
        // Get users who are following the term.
        $users = $this->eicFlagsHelper->getFlaggingUsersByFlagIds($entity, ['follow_taxonomy_term']);
        break;

    }

    return $users;
  }

}
