<?php

namespace Drupal\eic_message_subscriptions;

use Drupal\eic_messages\MessageIdentifierInterface;
use Drupal\eic_messages\MessageTemplateTypes;
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

  const NODE_PUBLISHED = 'sub_content_interest_published';

  const CONTENT_RECOMMENDED = 'sub_new_content_recommendation';

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
        $primary_keys = [
          'field_event_executing_user',
          'field_referenced_node',
        ];
        break;

    }

    return $primary_keys;
  }

}
