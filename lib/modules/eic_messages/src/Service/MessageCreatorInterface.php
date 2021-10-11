<?php

namespace Drupal\eic_messages\Service;

use Drupal\message\MessageInterface;
use Drupal\message\MessageTemplateInterface;

/**
 * Interface MessageCreatorInterface to implement in MessageCreator services.
 */
interface MessageCreatorInterface {

  /**
   * Checks if the given message should be created or not.
   *
   * @param Drupal\message\MessageInterface $message
   *   The message to be created.
   *
   * @return bool
   *   Whether to create the message or not.
   */
  public function shouldCreateNewMessage(MessageInterface $message);

  /**
   * Returns the fields used as primary keys for the given message template.
   *
   * @param \Drupal\message\MessageTemplateInterface $message_template
   *   The message template.
   *
   * @return array|string[]
   *   An array of field names.
   */
  public function getMessageTemplatePrimaryKeys(MessageTemplateInterface $message_template);

}
