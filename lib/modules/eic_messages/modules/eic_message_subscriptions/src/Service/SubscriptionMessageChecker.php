<?php

namespace Drupal\eic_message_subscriptions\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_user\NotificationFrequencies;
use Drupal\eic_user\NotificationTypes;
use Drupal\eic_user\Service\NotificationSettingsManager;
use Drupal\message\MessageInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use InvalidArgumentException;

/**
 * Class SubscriptionMessageChecker
 *
 * @package Drupal\eic_message_subscriptions\Service
 */
class SubscriptionMessageChecker {

  /**
   * Services which manages the notification settings for users.
   */
  private NotificationSettingsManager $notificationSettingsManager;

  /**
   * The messenger service.
   */
  private MessengerInterface $messenger;

  /**
   * @param \Drupal\eic_user\Service\NotificationSettingsManager $notification_settings_manager
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(
    NotificationSettingsManager $notification_settings_manager,
    MessengerInterface $messenger
  ) {
    $this->notificationSettingsManager = $notification_settings_manager;
    $this->messenger = $messenger;
  }

  /**
   * @param string $uid
   * @param \Drupal\message\MessageInterface $message
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function shouldSend(string $uid, MessageInterface $message): bool {
    $user = User::load($uid);
    if (!$user instanceof UserInterface) {
      return FALSE;
    }

    if (!$notification_type = MessageSubscriptionTypes::SUBSCRIPTION_MESSAGE_CATEGORIES[$message->bundle()]) {
      // By default, we suppose that every unsupported message is to be sent.
      return TRUE;
    }

    switch ($notification_type) {
      case NotificationTypes::GROUPS_NOTIFICATION_TYPE:
      case NotificationTypes::EVENTS_NOTIFICATION_TYPE:
        $result = $this->checkFollowFlagValue($user, $message, $notification_type);
        break;
      case NotificationTypes::INTEREST_NOTIFICATION_TYPE:
      case NotificationTypes::COMMENTS_NOTIFICATION_TYPE:
        $result = $this->notificationSettingsManager->getProfileSetting($user, $notification_type);
        break;
      default:
        throw new InvalidArgumentException(
          sprintf('Something is wrong with the notification category assigned to message %s', $message->bundle())
        );
    }

    return $result;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   * @param \Drupal\message\MessageInterface $message
   * @param string $notification_category
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function checkFollowFlagValue(
    UserInterface $user,
    MessageInterface $message,
    string $notification_category
  ): bool {
    $followed_entity = $this->getReferencedEntity($message);
    if (!$followed_entity instanceof ContentEntityInterface) {
      return TRUE;
    }

    $value = $this->notificationSettingsManager->getFollowFlagValue(
      $notification_category,
      $user,
      $followed_entity
    );

    return $value === NotificationFrequencies::ON;
  }

  /**
   * @param \Drupal\message\MessageInterface $message
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|NULL
   */
  private function getReferencedEntity(MessageInterface $message): ?ContentEntityInterface {
    $notification_type = MessageSubscriptionTypes::SUBSCRIPTION_MESSAGE_CATEGORIES[$message->bundle()];
    switch ($notification_type) {
      case NotificationTypes::GROUPS_NOTIFICATION_TYPE:
      case NotificationTypes::EVENTS_NOTIFICATION_TYPE:
        // Since only site-wide events(entity type: group) has a subscription message for the moment.
        // We assume the field to use is field_group_ref. Change this when a message for group events is introduced.
        $field = 'field_group_ref';
        break;
    }

    if (!$message->hasField($field)) {
      $this->messenger->addError(
        sprintf('Message template %s is supposed to have field %s, something seems wrong', $message->bundle(), $field)
      );

      return NULL;
    }

    $entities = $message->get($field)->referencedEntities();
    if (empty($entities)) {
      return NULL;
    }

    return reset($entities);
  }

}
