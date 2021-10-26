<?php

namespace Drupal\eic_messages\Handler;

use Drupal\message\MessageInterface;

interface MessageHandlerInterface {

  /**
   * Returns the stamps to apply to the message types supported by this
   * producer.
   *
   * @param \Drupal\message\MessageInterface $message
   *
   * @return \Drupal\eic_messages\Stamps\StampInterface[]
   */
  public function getStamps(MessageInterface $message): array;

  /**
   * Handles the given message and stamps. (E.g: by putting it in a queue)
   *
   * @param array $payload
   *   An array of stamps and the message entity.
   */
  public function handle(array $payload): void;

  /**
   * Returns the message type handled by this producer.
   * See MessageTemplateTypes.php
   *
   * @return string
   */
  public function getType(): string;

}
