<?php

namespace Drupal\eic_message_subscriptions\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_flags\FlagType;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\message\Entity\Message;

/**
 * Provides a message creator class for group content.
 *
 * @package Drupal\eic_message_subsriptions
 */
class SubscriptionMessageCreator {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(AccountProxyInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * Creates a subscription message for a node with terms of interest.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string $operation
   *   The type of the operations. See SubscriptionOperationTypes.
   * @param \Drupal\taxonomy\TermInterface[] $topics
   *
   * @return \Drupal\message\MessageInterface
   */
  public function createTermsOfInterestNodeSubscription(
    ContentEntityInterface $entity,
    string $operation,
    array $topics
  ) {
    $message = NULL;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message_type = $operation === SubscriptionOperationTypes::NEW_ENTITY
          ? MessageSubscriptionTypes::NODE_PUBLISHED
          : NULL;

        // We only create subscription message if the operation is 'created'.
        if (!$message_type) {
          break;
        }

        $message = Message::create([
          'template' => $message_type,
          'field_referenced_node' => $entity,
          'field_topic_term' => $topics,
        ]);

        $group_contents = GroupContent::loadByEntity($entity);

        if (!empty($group_contents)) {
          $group_content = reset($group_contents);
          $group = $group_content->getGroup();
          // Adds reference to group.
          $message->set('field_group_ref', $group);
        }

        // Set the owner of the message to the current user.
        $executing_user_id = $this->currentUser->id();
        $message->setOwnerId($executing_user_id);

        // Adds the reference to the user who created/updated the entity.
        if ($message->hasField('field_event_executing_user')) {
          $message->set('field_event_executing_user', $executing_user_id);
        }
        break;
    }

    return $message;
  }

  /**
   * Creates a subscription message to be sent when a user recommends a node.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging object.
   *
   * @return \Drupal\message\MessageInterface
   */
  public function createContentRecommendedSubscription(
    FlaggingInterface $flagging
  ) {
    $flag = $flagging->getFlag();
    $message = NULL;

    if ($flag->id() !== FlagType::RECOMMEND) {
      return $message;
    }

    // Gets the flagged entity.
    $flagged_entity = $flagging->getFlaggable();

    // Instantiates a new message entity.
    $message = Message::create([
      'template' => MessageSubscriptionTypes::CONTENT_RECOMMENDED,
      'field_referenced_node' => $flagged_entity,
    ]);

    // Adds the reference to the user who recommended the content.
    $message->set('field_event_executing_user', $flagging->getOwnerId());
    $message->setOwnerId($flagging->getOwnerId());

    return $message;
  }

  /**
   * Creates a subscription message for a comment.
   *
   * @param \Drupal\comment\CommentInterface $entity
   *   The comment entity.
   * @param string $operation
   *   The type of the operation. See SubscriptionOperationTypes.
   *
   * @return \Drupal\message\MessageInterface
   */
  public function createCommentSubscription(
    CommentInterface $entity,
    string $operation
  ) {
    $message_type = NULL;

    switch ($operation) {
      case SubscriptionOperationTypes::NEW_ENTITY:
        $message_type = MessageSubscriptionTypes::NEW_COMMENT;
        break;

      case SubscriptionOperationTypes::COMMENT_REPLY:
        $message_type = MessageSubscriptionTypes::NEW_COMMENT_REPLY;
        break;
    }

    if (!$message_type) {
      return NULL;
    }

    $message = Message::create([
      'template' => $message_type,
      'field_referenced_comment' => $entity,
    ]);

    // Set the owner of the message to the current user.
    $executing_user_id = $this->currentUser->id();
    $message->setOwnerId($executing_user_id);

    // Adds the reference to the user who created/updated the entity.
    if ($message->hasField('field_event_executing_user')) {
      $message->set('field_event_executing_user', $executing_user_id);
    }

    // Adds the reference to the commented entity.
    if ($message->hasField('field_referenced_node')) {
      $commented_entity = $entity->getCommentedEntity();
      if ($commented_entity instanceof ContentEntityInterface) {
        $message->set('field_referenced_node', $commented_entity->id());
      }
    }

    return $message;
  }


  /**
   * Creates a subscription message for an entity inside a group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group having this content.
   * @param string $operation
   *   The type of the operations. See SubscriptionOperationTypes.
   *
   * @return \Drupal\message\MessageInterface
   */
  public function createGroupContentSubscription(
    ContentEntityInterface $entity,
    GroupInterface $group,
    string $operation
  ) {
    $message = NULL;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message_type = $operation === SubscriptionOperationTypes::UPDATED_ENTITY
          ? MessageSubscriptionTypes::GROUP_CONTENT_UPDATED
          : MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED;

        $message = Message::create([
          'template' => $message_type,
          'field_referenced_node' => $entity,
          'field_group_ref' => $group,
        ]);

        // Set the owner of the message to the current user.
        $executing_user_id = $this->currentUser->id();
        $message->setOwnerId($executing_user_id);

        // Adds the reference to the user who created/updated the entity.
        if ($message->hasField('field_event_executing_user')) {
          $message->set('field_event_executing_user', $executing_user_id);
        }

        // @todo Set values for the missing fields.
        break;
    }

    return $message;
  }

}
