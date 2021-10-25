<?php

namespace Drupal\eic_messages\Service;

use Drupal\message\MessageInterface;

/**
 * Interface MessageCreatorInterface to implement in MessageCreator services.
 */
interface MessageBusInterface {

  /**
   * Checks if the given message should be created or not.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to be created.
   *
   * @return bool
   *   Whether to create the message or not.
   */
  public function shouldCreateNewMessage(MessageInterface $message): bool;

  /**
   * @param \Drupal\message\Entity\Message|array $message
   *   The envelope containing the data for the message.
   */
  public function dispatch($message): void;

}
