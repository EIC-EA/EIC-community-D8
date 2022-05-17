<?php

namespace Drupal\eic_subscription_digest\Constants;

use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;

/**
 * Class DigestSubscriptions
 */
final class DigestSubscriptions {

  /**
   * List of supported flags to use in the digest.
   */
  const SUPPORTED_MESSAGES = [
    MessageSubscriptionTypes::NODE_PUBLISHED,
    MessageSubscriptionTypes::GROUP_CONTENT_UPDATED,
    MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED,
    MessageSubscriptionTypes::NEW_EVENT_PUBLISHED,
    MessageSubscriptionTypes::NEW_COMMENT_REPLY,
    MessageSubscriptionTypes::NEW_COMMENT,
  ];

}
