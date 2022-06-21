<?php

namespace Drupal\eic_user;

/**
 * Class NotificationTypes
 *
 * @package Drupal\eic_user
 */
final class NotificationTypes {

  /**
   * List of allowed notification settings types.
   */
  const ALLOWED_NOTIFICATION_TYPES = [
    self::INTEREST_NOTIFICATION_TYPE,
    self::COMMENTS_NOTIFICATION_TYPE,
    self::EVENTS_NOTIFICATION_TYPE,
    self::GROUPS_NOTIFICATION_TYPE,
    self::ORGANISATION_NOTIFICATION_TYPE,
  ];

  /**
   * Targets the interest notification choice.
   * Value is stored in field 'field_interest_notifications'
   */
  const INTEREST_NOTIFICATION_TYPE = 'interest';

  /**
   * Targets the comment notification choice.
   * Value is stored in field 'field_comments_notifications'
   */
  const COMMENTS_NOTIFICATION_TYPE = 'comments';

  /**
   * Targets the event notification choice.
   * Value is defined by follow flags.
   */
  const EVENTS_NOTIFICATION_TYPE = 'events';

  /**
   * Targets the group notification choice.
   * Value is defined by follow flags.
   */
  const GROUPS_NOTIFICATION_TYPE = 'groups';

  /**
   * Targets the organisation notification choice.
   * Value is defined by follow flags.
   */
  const ORGANISATION_NOTIFICATION_TYPE = 'organisations';

}
