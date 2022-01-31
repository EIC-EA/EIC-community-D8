<?php

namespace Drupal\eic_messages;

use Drupal\message\MessageTemplateInterface;

/**
 * Interface MessageIdentifierInterface to implement Message types classes.
 */
interface MessageIdentifierInterface {

  /**
   * Returns the fields used as primary keys for the given message template.
   *
   * @param \Drupal\message\MessageTemplateInterface $message_template
   *   The message template.
   *
   * @return array|string[]
   *   An array of field names.
   */
  public static function getMessageTemplatePrimaryKeys(MessageTemplateInterface $message_template);

}
