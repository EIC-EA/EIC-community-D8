<?php

namespace Drupal\eic_message_subscriptions\Service;

use Drupal\Core\Entity\ContentEntityInterface;
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
   * @param \Drupal\eic_user\Service\NotificationSettingsManager $notification_settings_manager
   */
  public function __construct(NotificationSettingsManager $notification_settings_manager) {
    $this->notificationSettingsManager = $notification_settings_manager;
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

    if (!$notification_category = MessageSubscriptionTypes::SUBSCRIPTION_MESSAGES_INTEREST_CATEGORIES[$message->bundle()]) {
      // By default, we suppose that every unsupported message is to be sent.
      return TRUE;
    }

    $result = TRUE;
    switch ($notification_category) {
      case NotificationTypes::GROUPS_NOTIFICATION_TYPE:
      case NotificationTypes::EVENTS_NOTIFICATION_TYPE:
        $result = $this->checkFollowFlagValue($user, $message, $notification_category);
        break;
      case NotificationTypes::INTEREST_NOTIFICATION_TYPE:
      case NotificationTypes::COMMENTS_NOTIFICATION_TYPE:
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
    $value = $this->notificationSettingsManager->getValue(
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
    $notification_type = MessageSubscriptionTypes::SUBSCRIPTION_MESSAGES_INTEREST_CATEGORIES[$message->bundle()];
    switch ($notification_type) {
      case NotificationTypes::COMMENTS_NOTIFICATION_TYPE:
        $field = 'field_referenced_comment';
        break;
      case NotificationTypes::GROUPS_NOTIFICATION_TYPE:
        $field = 'field_group_ref';
        break;
    }

    $entities = $message->get($field)->referencedEntities();
    if (empty($entities)) {
      return NULL;
    }

    return reset($entities);
  }

}
