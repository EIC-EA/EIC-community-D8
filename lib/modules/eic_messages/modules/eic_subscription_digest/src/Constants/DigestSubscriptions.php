<?php

use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;

/**
 * Class DigestSubscriptions
 */
final class DigestSubscriptions {

  /**
   * List of supported flags to use in the digest.
   */
  const SUPPORTED_FLAGS = [
    MessageSubscriptionTypes::NODE_PUBLISHED,
    MessageSubscriptionTypes::GROUP_CONTENT_UPDATED,
    MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED,
    MessageSubscriptionTypes::NEW_EVENT_PUBLISHED,
  ];

}
