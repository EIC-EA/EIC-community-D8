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
   * Message template for notifying group ownership transfer.
   */
  const TRANSFER_GROUP_OWNERSHIP = 'notify_transfer_group_ownership';

  /**
   * Message template for notifying user when tagged on a comment.
   */
  const USER_TAGGED_ON_COMMENT = 'notify_user_tagged_on_comment';

  /**
   * Message template for notifying group visibility changes.
   */
  const GROUP_VISIBILITY_CHANGE = 'notify_group_access_change';

  /**
   * Message template for notifying group visibility changes.
   */
  const GROUP_DELETE = 'notify_group_deleted';

  /**
   * Message template for notifying an author of an admin update.
   */
  const CONTENT_UPDATE_BY_ADMIN = 'notify_own_group_content_updated';

  /**
   * Message template for notifying content recommendations.
   */
  const CONTENT_RECOMMENDATION = 'notify_content_recommendation';

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

    return $primary_keys;
  }

}
