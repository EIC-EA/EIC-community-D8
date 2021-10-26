<?php

namespace Drupal\eic_messages;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;

trait QueueItemProducerTrait {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   */
  public function setQueue(QueueFactory $queue_factory): void {
    $this->queueFactory = $queue_factory;
  }

  /**
   * Returns the queue matching to the given name.
   *
   * @param string $name
   *
   * @return \Drupal\Core\Queue\QueueInterface
   */
  public function getQueue(string $name): QueueInterface {
    return $this->queueFactory->get($name);
  }

  /**
   * Handles the given message and stamps. (E.g: by putting it in a queue)
   *
   * @param array $payload
   *   An array of stamps and the message entity.
   */
  public function handle(array $payload): void {
    $this->queueFactory->get($this->getQueueName())
      ->createItem($payload);
  }

  /**
   * @return string
   */
  public function getQueueName(): string {
    return $this->queueName;
  }

}
