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
  const NODE_UPDATE = 'eic_message_subscriptions.node_update';

}
