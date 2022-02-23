<?php

namespace Drupal\eic_user;

/**
 * Defines eic_user module constants.
 */
final class ProfileConst {

  /**
   * Defines array of allowed social networks.
   */
  const ALLOWED_SOCIAL_NETWORKS = [
    'linkedin',
    'twitter',
    'facebook',
  ];

  /**
   * Defines the member profile type name.
   */
  const MEMBER_PROFILE_TYPE_NAME = 'member';


  /**
   * Targets the interest notification choice.
   * Value is stored in field 'field_interest_notifications'
   */
  const INTEREST_NOTIFICATION_TYPE = 'interest';

  /**
   * List of allowed notification settings types.
   */
  const ALLOWED_NOTIFICATION_TYPES = [
    self::INTEREST_NOTIFICATION_TYPE,
    self::COMMENTS_NOTIFICATION_TYPE,
    self::EVENTS_NOTIFICATION_TYPE,
    self::GROUPS_NOTIFICATION_TYPE,
  ];

  /**
   * Targets the comments notification choice.
   * Value is stored in field 'field_comments_notifications'
   */
  const COMMENTS_NOTIFICATION_TYPE = 'comments';

  /**
   * Targets the events notification choice.
   * Value is defined by follow flags.
   */
  const EVENTS_NOTIFICATION_TYPE = 'events';

  /**
   * Targets the groups notification choice.
   * Value is defined by follow flags.
   */
  const GROUPS_NOTIFICATION_TYPE = 'groups';

}
