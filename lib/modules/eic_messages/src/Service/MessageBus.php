<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\State\StateInterface;
use Drupal\eic_messages\Handler\MessageHandlerInterface;
use Drupal\eic_messages\Util\QueuedMessageChecker;
use Drupal\eic_migrate\Commands\MigrateToolsOverrideCommands;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Exception;

class MessageBus implements MessageBusInterface {

  use LoggerChannelTrait;

  /**
   * The message notify queue worker.
   *
   * @var \Drupal\eic_messages\Plugin\QueueWorker\MessageNotifyQueueWorker
   */
  protected $queueFactory;

  /**
   * Array of registered message producers.
   *
   * @var \Drupal\eic_messages\Handler\MessageHandlerInterface[]
   */
  private $handlers;

  /**
   * @var \Drupal\eic_messages\Util\QueuedMessageChecker
   */
  private $queuedMessageChecker;

  /**
   * @var StateInterface
   */
  private $state;

  /**
   * @param \Drupal\eic_messages\Util\QueuedMessageChecker $queued_message_checker
   * @param StateInterface $state
   */
  public function __construct(
    QueuedMessageChecker $queued_message_checker,
    StateInterface $state
  ) {
    $this->queuedMessageChecker = $queued_message_checker;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch(
    $message,
    array $message_options = []
  ): void {
    // If we are running migrations, stop saving messages and sending notifications.
    if ($this->state->get(MigrateToolsOverrideCommands::STATE_MIGRATIONS_IS_RUNNING)) {
      return;
    }

    if (!$message instanceof MessageInterface) {
      $message = Message::create($message);
    }

    $logger = $this->getLogger('eic_messages');
    $type = $message->getTemplate()
      ->getThirdPartySetting('eic_messages', 'message_template_type');
    $handler = $this->getHandler($type);
    if (!$handler instanceof MessageHandlerInterface) {
      $logger->error('Unsupported message type @type', ['@type' => $type]);
      return;
    }

    if (!$this->queuedMessageChecker->shouldCreateNewMessage($message)) {
      return;
    }

    try {
      $message = [
        'stamps' => $handler->getStamps($message),
        'entity' => $message,
        'options' => $message_options,
      ];

      $handler->handle($message);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
    }
  }

  /**
   * Returns the handler matching to the given type.
   *
   * @param string $type
   *
   * @return \Drupal\eic_messages\Handler\MessageHandlerInterface|null
   */
  public function getHandler(string $type): ?MessageHandlerInterface {
    return $this->handlers[$type] ?? NULL;
  }

  /**
   * Registers the given handler.
   *
   * @param \Drupal\eic_messages\Handler\MessageHandlerInterface $handler
   */
  public function addHandler(MessageHandlerInterface $handler) {
    $this->handlers[$handler->getType()] = $handler;
  }

}
