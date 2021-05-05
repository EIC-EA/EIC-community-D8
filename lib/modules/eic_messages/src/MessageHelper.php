<?php

namespace Drupal\eic_messages;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\message\Entity\Message;
use Drupal\eic_user\UserHelper;

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
   * The EIC user helper class.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

  /**
   * Constructs a new MessageHelper object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC user helper.
   */
  public function __construct(QueueFactory $queue_factory, UserHelper $eic_user_helper) {
    $this->notifyQueue = $queue_factory->get('eic_message_notify_queue');
    $this->eicUserHelper = $eic_user_helper;
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
      $this->notifyQueue->createItem(['mid' => $message->id()]);
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }
  }

}
