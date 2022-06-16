<?php

namespace Drupal\eic_messages\Service;

/**
 * Interface MessageCreatorInterface to implement in MessageCreator services.
 */
interface MessageBusInterface {

  /**
   * Dispatch message to the queue.
   *
   * @param \Drupal\message\Entity\Message|array $message
   *   The envelope containing the data for the message.
   * @param array $message_options
   *   Optional message options to use in the message notifier.
   */
  public function dispatch($message, array $message_options = []): void;

}
