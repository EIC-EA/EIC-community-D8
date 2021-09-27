<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_message_subscriptions\MessageSubscriptionHelper;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\eic_messages\Service\CommentMessageCreator;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The EIC Flags helper sevice.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $eicFlagsHelper;

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $notifier;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The message subscription helper service.
   *
   * @var \Drupal\eic_message_subscriptions\MessageSubscriptionHelper
   */
  protected $messageSubscriptionHelper;

  /**
   * The Comment Message Creator service.
   *
   * @var \Drupal\eic_messages\Service\CommentMessageCreator
   */
  protected $commentMessageCreator;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\eic_flags\FlagHelper $eic_flags_helper
   *   The EIC Flags helper sevice.
   * @param \Drupal\message_notify\MessageNotifier $notifier
   *   The message notifier.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_message_subscriptions\MessageSubscriptionHelper $message_subscription_helper
   *   The message subscription helper service.
   * @param \Drupal\eic_messages\Service\CommentMessageCreator $comment_message_creator
   *   The Comment Message Creator service.
   */
  public function __construct(
    FlagHelper $eic_flags_helper,
    MessageNotifier $notifier,
    EntityTypeManagerInterface $entity_type_manager,
    MessageSubscriptionHelper $message_subscription_helper,
    CommentMessageCreator $comment_message_creator
  ) {
    $this->eicFlagsHelper = $eic_flags_helper;
    $this->notifier = $notifier;
    $this->entityTypeManager = $entity_type_manager;
    $this->messageSubscriptionHelper = $message_subscription_helper;
    $this->commentMessageCreator = $comment_message_creator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_flags.helper'),
      $container->get('message_notify.sender'),
      $container->get('entity_type.manager'),
      $container->get('eic_message_subscriptions.helper'),
      $container->get('eic_messages.message_creator.comment')
    );
  }

  /**
   * Implements hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityInsert(EntityInterface $entity) {
    $operation = FALSE;
    /** @var \Drupal\user\Entity\User[] $subscribed_users */
    $subscribed_users = [];

    if (!$this->messageSubscriptionHelper->isMessageSubscriptionApplicable($entity)) {
      return;
    }

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Default operation when new comment is added.
        $operation = SubscriptionOperationTypes::NEW_ENTITY;

        // If comment is a reply we set the operation to 'comment_reply'..
        if ($entity->hasParentComment()) {
          $operation = SubscriptionOperationTypes::COMMENT_REPLY;
        }

        // Get commented entity.
        $commented_entity = $entity->getCommentedEntity();
        // Get users who are following the commented entity.
        $subscribed_users = $this->eicFlagsHelper->getFlaggingUsersByFlagIds($commented_entity, ['follow_content']);
        break;

    }

    // No message type defined so we do nothing.
    if (!$operation) {
      return;
    }

    $message = NULL;

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        $message = $this->commentMessageCreator->createCommentSubscription(
          $entity,
          $operation
        );
        break;

    }

    if (!$message) {
      return;
    }

    foreach ($subscribed_users as $user) {
      $message->setOwnerId($user->id());
      // @todo Send message to a queue to be processed later by cron.
      $this->notifier->send($message);
    }
  }

}
