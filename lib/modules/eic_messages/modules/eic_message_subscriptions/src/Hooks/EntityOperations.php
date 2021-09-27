<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_message_subscriptions\MessageSubscriptionType;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 *
 * @package Drupal\eic_message_subscriptions\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The Flag sevice.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The Flag sevice.
   * @param \Drupal\message_notify\MessageNotifier $notifier
   *   The message notifier.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ModerationInformationInterface $moderation_information,
    FlagServiceInterface $flag_service,
    MessageNotifier $notifier,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->moderationInformation = $moderation_information;
    $this->flagService = $flag_service;
    $this->notifier = $notifier;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('flag'),
      $container->get('message_notify.sender'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Implements hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityInsert(EntityInterface $entity) {
    $subscription_message_type = FALSE;
    /** @var \Drupal\user\Entity\User[] $subscribed_users */
    $subscribed_users = [];

    if (!$this->isMessageSubscriptionApplicable($entity)) {
      return;
    }

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Default message type when new comment is added.
        $subscription_message_type = MessageSubscriptionType::NEW_COMMENT;

        // If comment is a reply we set the message type to
        // "sub_new_comment_reply".
        if ($entity->hasParentComment()) {
          $subscription_message_type = MessageSubscriptionType::NEW_COMMENT_REPLY;
        }

        // Get commented entity.
        $commented_entity = $entity->getCommentedEntity();
        // Get users who are following the commented entity.
        $subscribed_users = $this->getFlaggingUsersByFlagId($commented_entity, 'follow_content');
        break;

      case 'group_content':
        // Default message type when new group content is added.
        $subscription_message_type = MessageSubscriptionType::NEW_GROUP_CONTENT_PUBLISHED;
        // Get the group entity.
        $group = $entity->getGroup();
        // Get users who are following the commented entity.
        $subscribed_users = $this->getFlaggingUsersByFlagId($group, 'follow_group');
        break;

    }

    // No message type defined so we do nothing.
    if (!$subscription_message_type) {
      return;
    }

    foreach ($subscribed_users as $user) {
      $message = $this->prepareMessage($subscription_message_type, $entity, $user);

      if (!$message) {
        continue;
      }

      // @todo Send message to a queue to be processed later by cron.
      $this->notifier->send($message);
    }
  }

  /**
   * Prepares a new message entity.
   *
   * @param string $message_type
   *   The message type ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which triggered the message.
   * @param \Drupal\user\UserInterface $user
   *   The owner of the message.
   *
   * @return \Drupal\message\MessageInterface
   *   The message entity to be sent out.
   */
  private function prepareMessage($message_type, EntityInterface $entity, UserInterface $user) {
    $message = Message::create([
      'template' => $message_type,
      'uid' => $user->id(),
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

    switch ($message_type) {
      case MessageSubscriptionType::NEW_COMMENT:
        // @todo Set values for the missing fields.
        break;

      case MessageSubscriptionType::NEW_COMMENT_REPLY:
        // @todo Set values for the missing fields.
        break;

      case MessageSubscriptionType::NEW_GROUP_CONTENT_PUBLISHED:
        // @todo Set values for the missing fields.
        break;

    }

    return $message;
  }

  /**
   * Get a list of users that have flagged an entity with a given flag ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flagged entity.
   * @param string $flag_id
   *   The flag machine name.
   *
   * @return array
   *   An array of users who have flagged the entity.
   */
  private function getFlaggingUsersByFlagId(EntityInterface $entity, $flag_id) {
    if (empty($flag_id)) {
      return $this->flagService->getFlaggingUsers($entity);
    }

    $flag = $this->flagService->getFlagById($flag_id);

    if (!$flag) {
      return [];
    }

    $result = [];
    $flagging_users = $this->flagService->getFlaggingUsers($entity, $flag);

    foreach ($flagging_users as $user) {
      $result[$user->id()] = $user;
    }

    return $result;
  }

  /**
   * Check if an entity can trigger message subscriptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if the entity can trigger message subscriptions.
   */
  private function isMessageSubscriptionApplicable(EntityInterface $entity) {
    $is_applicable = TRUE;
    $in_group_context = FALSE;

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Get commented entity.
        $commented_entity = $entity->getCommentedEntity();

        // Loads group contents for the commented entity.
        $group_contents = GroupContent::loadByEntity($commented_entity);

        if (!empty($group_contents)) {
          break;
        }

        $group_content = reset($group_contents);

        $in_group_context = TRUE;
        break;

      case 'node':
        // Loads group contents for the node.
        $group_contents = GroupContent::loadByEntity($entity);

        if (!empty($group_contents)) {
          break;
        }

        $group_content = reset($group_contents);

        $in_group_context = TRUE;
        break;

      case 'group_content':
        $group_content = $entity;
        $in_group_context = TRUE;
        break;

    }

    // If the entity is in the context of a group we need to make sure the
    // group is not in pending or draft state.
    if ($in_group_context && isset($group_content)) {
      $group_content_plugin_id = $entity->getContentPlugin()->getPluginId();

      // Group content plugins other than group_node cannot trigger
      // notifications.
      if (strpos($group_content_plugin_id, 'group_node:') === FALSE) {
        return FALSE;
      }

      // Group book pages cannot trigger notifications.
      if (strpos($group_content_plugin_id, 'group_node:book') !== FALSE) {
        return FALSE;
      }

      // If entity is not publish, it cannot trigger notifications.
      if (!$group_content->getEntity()->isPublished()) {
        return FALSE;
      }

      $group = $entity->getGroup();

      $moderation_state = $group->get('moderation_state')->value;

      $is_applicable = !in_array(
        $moderation_state,
        [
          GroupsModerationHelper::GROUP_PENDING_STATE,
          GroupsModerationHelper::GROUP_DRAFT_STATE,
        ]
      );
    }

    return $is_applicable;
  }

}
