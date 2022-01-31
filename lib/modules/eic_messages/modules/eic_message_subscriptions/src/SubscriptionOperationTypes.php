<?php

namespace Drupal\eic_message_subscriptions;

/**
 * Represents entity operations to triggers message subscriptions.
 *
 * @package Drupal\eic_message_subscriptions
 */
final class SubscriptionOperationTypes {

  const NEW_ENTITY = 'created';

  const UPDATED_ENTITY = 'updated';

  const COMMENT_REPLY = 'comment_reply';

}
