<?php

namespace Drupal\eic_messages\Handler;

use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\eic_messages\QueueItemProducerTrait;
use Drupal\eic_messages\Stamps\PersistentMessageStamp;
use Drupal\message\MessageInterface;

class StreamHandler implements MessageHandlerInterface {

  use QueueItemProducerTrait;

  protected $queueName = 'eic_message_notify_queue';

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return MessageTemplateTypes::STREAM;
  }

  /**
   * {@inheritdoc}
   */
  public function getStamps(MessageInterface $message): array {
    return [
      PersistentMessageStamp::class,
    ];
  }

}