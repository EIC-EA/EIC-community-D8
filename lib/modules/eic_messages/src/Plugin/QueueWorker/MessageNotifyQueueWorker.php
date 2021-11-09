<?php

namespace Drupal\eic_messages\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends out message notifications.
 *
 * @QueueWorker(
 *   id = "eic_message_notify_queue",
 *   title = @Translation("EIC Message Notify Queue"),
 *   cron = {"time" = 60}
 * )
 */
class MessageNotifyQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $notifier;

  /**
   * Constructor for the queue worker.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\message_notify\MessageNotifier $notifier
   *   The message notifier.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container, EntityTypeManagerInterface $entityTypeManager, MessageNotifier $notifier) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
    $this->entityTypeManager = $entityTypeManager;
    $this->notifier = $notifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');
    /** @var \Drupal\message_notify\MessageNotifier $notifier */
    $notifier = $container->get('message_notify.sender');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container,
      $entityTypeManager,
      $notifier
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Get the message.
    $message = NULL;
    $conf = [];
    if (!empty($data['mid'])) {
      $message = Message::load($data['mid']);
    }
    elseif ($data['message']) {
      $message = $data['message'];
      // If message is not saved, avoid saving it in the postSend() method.
      $conf['save on success'] = FALSE;
    }

    // Send the message.
    if ($message) {
      $this->notifier->send($message, $conf, 'email');
    }
  }

}
