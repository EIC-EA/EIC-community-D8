<?php

namespace Drupal\eic_messages\Service;

use Drupal\comment\CommentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\eic_user\UserHelper;
use Drupal\message\Entity\Message;

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
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime.time service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
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
    TimeInterface $date_time,
    ConfigFactory $config_factory,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    MessageHelper $eic_messages_helper,
    UserHelper $eic_user_helper,
    EICContentHelperInterface $content_helper
  ) {
    parent::__construct(
      $date_time,
      $config_factory,
      $current_user,
      $entity_type_manager,
      $eic_messages_helper,
      $eic_user_helper
    );

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
   * @param string $operation
   *   The type of the operation. See SubscriptionOperationTypes.
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

    return $message;
  }

}
