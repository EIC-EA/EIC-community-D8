<?php

namespace Drupal\eic_messages\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eic_messages\Stamps\PersistentMessageStamp;
use Drupal\eic_user\UserHelper;
use Drupal\message\MessageInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;
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
   * @param \Drupal\message_notify\MessageNotifier $notifier
   *   The message notifier.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MessageNotifier $notifier
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->notifier = $notifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    /** @var \Drupal\message_notify\MessageNotifier $notifier */
    $notifier = $container->get('message_notify.sender');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $notifier
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['entity']) || !$data['entity'] instanceof MessageInterface) {
      return;
    }

    // Get the message.
    $message = $data['entity'];
    if (
      isset($data['stamps'])
      && in_array(PersistentMessageStamp::class, $data['stamps'])
    ) {
      $message->save();
    }

    $owner = $message->getOwner();
    if (!$owner instanceof UserInterface || !filter_var(
        $owner->getEmail(),
        FILTER_VALIDATE_EMAIL
      )) {
      return;
    }

    if (UserHelper::isUserBlockedOrUnsubscribed($owner)) {
      return;
    }

    $options = [];
    if (isset($data['options'])) {
      $options = $data['options'];
    }
    // Notifier shouldn't care whether the message is saved or not!
    $options['save on fail'] = FALSE;
    $options['save on success'] = FALSE;

    $this->notifier->send(
      $message,
      $options
    );
  }

}
