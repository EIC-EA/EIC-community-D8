<?php

namespace Drupal\eic_flags\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
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

  use LoggerChannelTrait;

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
   * The message notify queue worker.
   *
   * @var \Drupal\eic_messages\Plugin\QueueWorker\MessageNotifyQueueWorker
   */
  protected $notifyQueue;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $message = NULL;

    switch ($data['flag_id']) {
      case 'like_content':
        $message = $this->processContentItem($data);
        break;

      case 'like_media':
        $message = $this->processMediaItem($data);
        break;

      case 'like_comment':
        $message = $this->processCommentItem($data);
        break;
    }

    if ($message) {
      try {
        // Save the message and create the message notify queue item.
        $message->save();
        $this->notifyQueue->createItem(['mid' => $message->id()]);
      } catch (\Exception $e) {
        $logger = $this->getLogger('eic_flags');
        $logger->error($e->getMessage());
      }
    }
  }

  /**
   * Processes node notifications.
   *
   * @param array $data
   *   Data needed for processing.
   *
   * @return \Drupal\message\MessageInterface
   *   The message to be added.
   */
  private function processContentItem(array $data) {
    $node = $this->entityTypeManager->getStorage('node')->load(
      $data['entity_id']
    );
    $message = Message::create([
      'template' => 'like_content',
      'uid' => $node->getOwnerId(),
    ]);
    $message->set('field_referenced_node', $node);

    return $message;
  }

  /**
   * Processes media notifications.
   *
   * @param array $data
   *   Data needed for processing.
   *
   * @return \Drupal\message\MessageInterface
   *   The message to be added.
   */
  private function processMediaItem(array $data) {
    $media = $this->entityTypeManager->getStorage('media')->load(
      $data['entity_id']
    );
    $message = Message::create([
      'template' => 'like_media',
      'uid' => $media->getOwnerId(),
    ]);
    $message->set('field_referenced_media', $media);

    return $message;
  }

  /**
   * Processes comment notifications.
   *
   * @param array $data
   *   Data needed for processing.
   *
   * @return \Drupal\message\MessageInterface
   *   The message to be added.
   */
  private function processCommentItem(array $data) {
    $comment = $this->entityTypeManager->getStorage('comment')->load(
      $data['entity_id']
    );
    $node = $this->entityTypeManager->getStorage('node')->load(
      $comment->getCommentedEntityId()
    );
    $message = Message::create([
      'template' => 'like_comment',
      'uid' => $node->getOwnerId(),
    ]);
    $message->set('field_referenced_node', $node);

    return $message;
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
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $queue_factory = $container->get('queue');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container,
      $entityTypeManager,
      $queue_factory
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
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContainerInterface $container,
    EntityTypeManagerInterface $entityTypeManager,
    QueueFactory $queue_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
    $this->entityTypeManager = $entityTypeManager;
    $this->notifyQueue = $queue_factory->get('eic_message_notify_queue');
  }

}
