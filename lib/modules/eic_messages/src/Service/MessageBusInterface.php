<?php

namespace Drupal\eic_messages\Service;

/**
 * Interface MessageCreatorInterface to implement in MessageCreator services.
 */
interface MessageBusInterface {

  /**
   * @param \Drupal\message\Entity\Message|array $message
   *   The envelope containing the data for the message.
   */
  public function dispatch($message): void;

}
