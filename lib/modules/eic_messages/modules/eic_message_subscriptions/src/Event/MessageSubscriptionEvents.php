<?php

namespace Drupal\eic_message_subscriptions\Event;

/**
 * Contains all events thrown in the eic_message_subscription module.
 */
final class MessageSubscriptionEvents {

  /**
   * Event ID for when a group content is created.
   *
   * @Event
   *
   * @var string
   */
  const GROUP_CONTENT_INSERT = 'eic_message_subscriptions.group_content_insert';

  /**
   * Event ID for when a group content is updated.
   *
   * @Event
   *
   * @var string
   */
  const GROUP_CONTENT_UPDATE = 'eic_message_subscriptions.group_content_update';

  /**
   * Event ID for when a comment is created.
   *
   * @Event
   *
   * @var string
   */
  const COMMENT_INSERT = 'eic_message_subscriptions.comment_insert';

  /**
   * Event ID for when a group content is updated.
   *
   * @Event
   *
   * @var string
   */
  const NODE_INSERT = 'eic_message_subscriptions.node_insert';

  /**
   * Event ID for when a group content is updated.
   *
   * @Event
   *
   * @var string
   */
  const CONTENT_RECOMMENDED = 'eic_message_subscriptions.content_recommended';

  /**
   * Event ID for when a new global event is created.
   *
   * @Event
   *
   * @var string
   */
  const GLOBAL_EVENT_INSERT = 'eic_message_subscriptions.global_event_insert';

  /**
   * Get message subscriptions events as array.
   *
   * @return array
   *   Array of message subscription events.
   */
  public static function getEventsArray() {
    return [
      self::GROUP_CONTENT_INSERT,
      self::GROUP_CONTENT_UPDATE,
      self::COMMENT_INSERT,
      self::NODE_INSERT,
      self::CONTENT_RECOMMENDED,
      self::GLOBAL_EVENT_INSERT,
    ];
  }

}
