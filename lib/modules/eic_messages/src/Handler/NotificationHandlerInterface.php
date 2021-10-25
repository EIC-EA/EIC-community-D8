<?php

namespace Drupal\eic_messages\Handler;

use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\eic_messages\QueueItemProducerTrait;
use Drupal\message\MessageInterface;

class NotificationHandler implements MessageHandlerInterface {

  use QueueItemProducerTrait;

  protected $queueName = 'eic_message_notify_queue';

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return MessageTemplateTypes::NOTIFICATION;
  }

  /**
   * {@inheritdoc}
   */
  public function getStamps(MessageInterface $message): array {
    return [];
  }

}