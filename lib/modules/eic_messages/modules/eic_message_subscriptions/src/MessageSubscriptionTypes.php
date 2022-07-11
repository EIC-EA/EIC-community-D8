<?php

namespace Drupal\eic_message_subscriptions;

use Drupal\eic_messages\MessageIdentifierInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\eic_user\NotificationTypes;
use Drupal\message\MessageTemplateInterface;

/**
 * Represents Message Subscription types.
 *
 * @package Drupal\eic_message_subscriptions
 */
final class MessageSubscriptionTypes implements MessageIdentifierInterface {

  const NEW_COMMENT_REPLY = 'sub_new_comment_reply';

  const NEW_COMMENT = 'sub_new_comment_on_content';

  const NEW_GROUP_CONTENT_PUBLISHED = 'sub_new_group_content_published';

  const GROUP_CONTENT_UPDATED = 'sub_group_content_updated';

  const GROUP_CONTENT_SHARED = 'sub_group_content_shared';

  const NEW_EVENT_PUBLISHED = 'sub_site_event_pub_interest';

  const NODE_PUBLISHED = 'sub_content_interest_published';

  const CONTENT_RECOMMENDED = 'sub_new_content_recommendation';

  /**
   * Categorises each subscription message using notification types defined in eic_user.
   * These messages are supposed to be "unsubscribable/deniable". Meaning that the user can
   * choose to not receive them.
   */
  const SUBSCRIPTION_MESSAGE_CATEGORIES = [
    self::NEW_COMMENT_REPLY => NotificationTypes::COMMENTS_NOTIFICATION_TYPE,
    self::NEW_COMMENT => NotificationTypes::COMMENTS_NOTIFICATION_TYPE,
    self::NEW_GROUP_CONTENT_PUBLISHED => NotificationTypes::GROUPS_NOTIFICATION_TYPE,
    self::GROUP_CONTENT_UPDATED => NotificationTypes::GROUPS_NOTIFICATION_TYPE,
    self::NODE_PUBLISHED => NotificationTypes::INTEREST_NOTIFICATION_TYPE,
    self::CONTENT_RECOMMENDED => NotificationTypes::INTEREST_NOTIFICATION_TYPE,
    self::NEW_EVENT_PUBLISHED => NotificationTypes::INTEREST_NOTIFICATION_TYPE,
    self::GROUP_CONTENT_SHARED => NotificationTypes::GROUPS_NOTIFICATION_TYPE,
  ];

  /**
   * Get message subscriptions events as array.
   *
   * @return array
   *   Array of message subscription events.
   */
  public static function getMessageSubscriptionTypesAsArray() {
    return [
      self::NEW_COMMENT_REPLY,
      self::NEW_COMMENT,
      self::NEW_GROUP_CONTENT_PUBLISHED,
      self::GROUP_CONTENT_UPDATED,
      self::NODE_PUBLISHED,
      self::CONTENT_RECOMMENDED,
      self::NEW_EVENT_PUBLISHED,
      self::GROUP_CONTENT_SHARED,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getMessageTemplatePrimaryKeys(MessageTemplateInterface $message_template) {
    $primary_keys = [];

    // Get the message template type.
    $message_template_type = $message_template->getThirdPartySetting('eic_messages', 'message_template_type');
    if ($message_template_type != MessageTemplateTypes::SUBSCRIPTION) {
      return FALSE;
    }

    switch ($message_template->id()) {
      case MessageSubscriptionTypes::GROUP_CONTENT_UPDATED:
      case MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED:
        $primary_keys = [
          'field_event_executing_user',
          'field_referenced_node',
        ];
        break;

    }

    return $primary_keys;
  }

  /**
   * @param string $type
   *
   * @return string|null
   */
  public static function getNotificationCategory(string $type): ?string {
    return self::SUBSCRIPTION_MESSAGE_CATEGORIES[$type] ?? NULL;
  }

}
