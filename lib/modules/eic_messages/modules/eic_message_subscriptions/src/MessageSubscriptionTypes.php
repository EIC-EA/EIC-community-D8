<?php

namespace Drupal\eic_message_subscriptions;

/**
 * Represents Message Subscription types.
 *
 * @package Drupal\eic_message_subscriptions
 */
final class MessageSubscriptionTypes {

  const NEW_COMMENT_REPLY = 'sub_new_comment_reply';

  const NEW_COMMENT = 'sub_new_comment_on_content';

  const NEW_GROUP_CONTENT_PUBLISHED = 'sub_new_group_content_published';

  const GROUP_CONTENT_UPDATED = 'sub_group_content_updated';

  const NODE_PUBLISHED = 'sub_content_interest_published';

  const NODE_UPDATED = NULL;

  const CONTENT_RECOMMENDED = 'sub_new_content_recommendation';

}
