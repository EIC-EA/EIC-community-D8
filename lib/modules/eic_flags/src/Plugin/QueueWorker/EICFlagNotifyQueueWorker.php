<?php

namespace Drupal\eic_flags\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if a user should get a notification about trials.
 *
 * @QueueWorker(
 *   id = "eic_flags_notify_queue",
 *   title = @Translation("EIC Flag Notifications Queue"),
 *   cron = {"time" = 60}
 * )
 */
class EICFlagNotifyQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function processItem($data) {
    switch ($data['flag_id']) {
      case 'like_content':
        $this->processContentItem($data);
        break;

      case 'like_media':
        $this->processMediaItem($data);
        break;

      case 'like_comment':
        $this->processCommentItem($data);
        break;
    }
  }

  /**
   * Processes node notifications.
   *
   * @param array $data
   *   Data needed for processing.
   */
  private function processContentItem(array $data) {
    $node = $this->entityTypeManager->getStorage('node')->load($data['entity_id']);
    $message = Message::create([
      'template' => 'like_content',
      'uid' => $node->getOwnerId(),
    ]);
    $message->set('field_referenced_node', $node);
    $this->notifier->send($message, [], 'email');
  }

  /**
   * Processes media notifications.
   *
   * @param array $data
   *   Data needed for processing.
   */
  private function processMediaItem(array $data) {
    $media = $this->entityTypeManager->getStorage('media')->load($data['entity_id']);
    $message = Message::create([
      'template' => 'like_media',
      'uid' => $media->getOwnerId(),
    ]);
    $message->set('field_referenced_media', $media);
    $this->notifier->send($message, [], 'email');
  }

  /**
   * Processes comment notifications.
   *
   * @param array $data
   *   Data needed for processing.
   */
  private function processCommentItem(array $data) {
    $comment = $this->entityTypeManager->getStorage('comment')->load($data['entity_id']);
    $node = $this->entityTypeManager->getStorage('node')->load($comment->getCommentedEnityId());
    $message = Message::create([
      'template' => 'like_comment',
      'uid' => $node->getOwnerId(),
    ]);
    $message->set('field_referenced_node', $node);
    $this->notifier->send($message, [], 'email');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
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
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContainerInterface $container,
    EntityTypeManagerInterface $entityTypeManager,
    MessageNotifier $notifier
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
    $this->entityTypeManager = $entityTypeManager;
    $this->notifier = $notifier;
  }

}
