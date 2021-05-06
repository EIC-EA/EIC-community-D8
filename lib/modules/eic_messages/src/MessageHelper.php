<?php

namespace Drupal\eic_messages;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\message\Entity\Message;

/**
 * MessageHelper service that provides helper functions for messages.
 */
class MessageHelper {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The message notify queue worker.
   *
   * @var \Drupal\eic_messages\Plugin\QueueWorker\MessageNotifyQueueWorker
   */
  protected $notifyQueue;

  /**
   * Constructs a new MessageHelper object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->notifyQueue = $queue_factory->get('eic_message_notify_queue');
  }

  /**
   * Adds a message to the notification queue.
   *
   * @param \Drupal\message\Entity\Message $message
   *   The message object to be sent. This message needs to be saved beforehand.
   */
  public function queueMessageNotification(Message $message) {
    try {
      // Create the message notify queue item.
      if (!empty($message->id())) {
        // If this is a saved messaged, just pass the mid.
        $this->notifyQueue->createItem(['mid' => $message->id()]);
      }
      else {
        // Otherwise pass the message object.
        $this->notifyQueue->createItem(['message' => $message]);
      }
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }
  }

}
