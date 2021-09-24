<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagServiceInterface;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 *
 * @package Drupal\eic_flags\Hooks
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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The Flag sevice.
   * @param \Drupal\message_notify\MessageNotifier $notifier
   *   The message notifier.
   */
  public function __construct(
    ModerationInformationInterface $moderation_information,
    FlagServiceInterface $flag_service,
    MessageNotifier $notifier
  ) {
    $this->moderationInformation = $moderation_information;
    $this->flagService = $flag_service;
    $this->notifier = $notifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('flag'),
      $container->get('message_notify.sender')
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

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Default message type when new comment is added.
        $subscription_message_type = 'sub_new_comment_on_content';

        // If comment is a reply we set the message type to
        // "sub_new_comment_reply".
        if ($entity->hasParentComment()) {
          $subscription_message_type = 'sub_new_comment_reply';
        }

        // Get commented entity.
        $commented_entity = $entity->getCommentedEntity();
        // Get users who are following the commented entity.
        $subscribed_users = $this->getFlaggingUsersByFlagId($commented_entity, 'follow_content');
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

    // Adds the reference to the owner of the entity.
    if ($message->hasField('field_event_executing_user')) {
      $message->set('field_event_executing_user', $entity->getOwnerId());
    }

    switch ($message_type) {
      case 'sub_new_comment_on_content':
        // @todo Set values for the missing fields.
        break;

      default:
        return FALSE;
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

}
