<?php

namespace Drupal\eic_messages\Service;

use Drupal\message\MessageTemplateInterface;

/**
 * Interface MessageCreatorInterface to implement in MessageCreator services.
 */
interface MessageCreatorInterface {

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
