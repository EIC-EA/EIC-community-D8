<?php

namespace Drupal\eic_messages\Util;

use Drupal\eic_messages\MessageIdentifierInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\message\MessageTemplateInterface;

/**
 * Helper class for activity stream message templates.
 */
final class NotificationMessageTemplates implements MessageIdentifierInterface {

  /**
   * Message template for blocked entities.
   */
  const ENTITY_BLOCKED = 'notify_entity_blocked';

  /**
   * Message template for notifying group ownership transfer.
   */
  const TRANSFER_GROUP_OWNERSHIP = 'notify_transfer_group_ownership';

  /**
   * {@inheritdoc}
   */
  public static function getMessageTemplatePrimaryKeys(MessageTemplateInterface $message_template) {
    $primary_keys = [];

    // Get the message template type.
    $message_template_type = $message_template->getThirdPartySetting('eic_messages', 'message_template_type');

    if ($message_template_type != MessageTemplateTypes::NOTIFICATION) {
      return FALSE;
    }

    switch ($message_template->id()) {

      case self::ENTITY_BLOCKED:
        $primary_keys = [
          'field_event_executing_user',
        ];
        break;

    }

    return $primary_keys;
  }

}
