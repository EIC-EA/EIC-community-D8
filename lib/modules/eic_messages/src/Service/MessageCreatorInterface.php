<?php

namespace Drupal\eic_messages\Service;

use Drupal\message\MessageInterface;

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

}
