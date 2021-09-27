<?php

namespace Drupal\eic_messages\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\eic_user\UserHelper;
use Drupal\message\Entity\Message;
use Drupal\user\UserInterface;

/**
 * Provides a message creator class for comments.
 *
 * @package Drupal\eic_messages
 */
class CommentMessageCreator extends MessageCreatorBase {

  /**
   * The EIC Content helper service.
   *
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  private $contentHelper;

  /**
   * CommentMessageCreator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   *   The EIC Message helper service.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   * @param \Drupal\eic_content\EICContentHelperInterface $content_helper
   *   The EIC Content helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessageHelper $eic_messages_helper,
    UserHelper $eic_user_helper,
    EICContentHelperInterface $content_helper
  ) {
    parent::__construct($entity_type_manager, $eic_messages_helper, $eic_user_helper);

    $this->contentHelper = $content_helper;
  }

  /**
   * Creates an activity stream message for a comment that belongs to a group.
   *
   * @param \Drupal\comment\CommentInterface $entity
   *   The group having this content.
   * @param string $operation
   *   The type of the operation. See ActivityStreamOperationTypes.
   */
  public function createCommentActivity(
    CommentInterface $entity,
    string $operation
  ) {
    $route_match = \Drupal::routeMatch();
    if (!in_array($route_match->getRouteName(), ['comment.reply', 'entity.comment.edit_form'])) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $commented_entity */
    $commented_entity = $entity->getCommentedEntity();
    $group_content = $this->contentHelper->getGroupContentByEntity($commented_entity);
    if (empty($group_content)) {
      return;
    }

    $group_content = reset($group_content);
    $group = $group_content->getGroup();
    $message = $this->entityTypeManager->getStorage('message')->create([
      'template' => ActivityStreamMessageTemplates::getTemplate($entity),
      'field_referenced_comment' => $entity,
      'field_referenced_node' => $commented_entity,
      'field_entity_type' => $entity->bundle(),
      'field_operation_type' => $operation,
      'field_group_ref' => $group,
    ]);

    try {
      $message->save();
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }
  }

  /**
   * Creates a subscription message for a comment.
   *
   * @param \Drupal\comment\CommentInterface $entity
   *   The comment entity.
   * @param \Drupal\user\UserInterface $user
   *   The user entity that is subscribed to the content.
   * @param string $operation
   *   The type of the operation. See SubscriptionOperationTypes.
   */
  public function createCommentSubscription(
    CommentInterface $entity,
    UserInterface $user,
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
      'uid' => $user->id(),
      'field_referenced_comment' => $entity,
    ]);

    // Adds the reference to the user who created/updated the entity.
    if ($message->hasField('field_event_executing_user')) {
      $executing_user_id = $entity->getOwnerId();

      $vid = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
        ->getLatestRevisionId($entity->id());

      if ($vid) {
        $latest_revision = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
          ->loadRevision($vid);
        $executing_user_id = $latest_revision->getOwnerId();
      }

      $message->set('field_event_executing_user', $executing_user_id);
    }

    return $message;
  }

}
