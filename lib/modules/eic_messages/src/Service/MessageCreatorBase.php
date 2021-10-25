<?php

namespace Drupal\eic_messages\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\eic_user\UserHelper;
use Drupal\message\MessageInterface;
use Drupal\message\MessageTemplateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for sending out messages.
 */
class MessageCreatorBase implements ContainerInjectionInterface, MessageCreatorInterface {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC Message helper service.
   *
   * @var \Drupal\eic_messages\MessageHelper
   */
  protected $eicMessagesHelper;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

  /**
   * Constructs a new MessageCreatorBase object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime.time service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   *   The EIC Message helper service.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   */
  public function __construct(TimeInterface $date_time, ConfigFactory $config_factory, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MessageHelper $eic_messages_helper, UserHelper $eic_user_helper) {
    $this->timeService = $date_time;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->eicMessagesHelper = $eic_messages_helper;
    $this->eicUserHelper = $eic_user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('eic_messages.helper'),
      $container->get('eic_user.helper')
    );
  }

  /**
   * Process an array of messages for queue notifications.
   *
   * @param array $messages
   *   The messages to be processed.
   */
  public function processMessages(array $messages) {
    foreach ($messages as $message) {
      try {
        // Create the message notify queue item.
        // @todo check if this type of message should live/stay in the DB.
        $this->eicMessagesHelper->queueMessageNotification($message);
      }
      catch (\Exception $e) {
        $logger = $this->getLogger('eic_messages');
        $logger->error($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * This method can be overridden in the extending classes if necessary.
   */
  public function shouldCreateNewMessage(MessageInterface $message) {
    // Check if message is of type subscription or activity stream.
    // If it is not, then a new message should be created.
    $message_template_type = $message->getTemplate()->getThirdPartySetting('eic_messages', 'message_template_type');
    $anti_spammed_types = [
      MessageTemplateTypes::SUBSCRIPTION,
      MessageTemplateTypes::STREAM,
    ];
    if (!in_array($message_template_type, $anti_spammed_types)) {
      return TRUE;
    }

    // Do not create messages for unpublished nodes.
    if ($message->hasField('field_referenced_node')) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $message->get('field_referenced_node')->referencedEntities()[0];
      if (!$node->isPublished()) {
        return FALSE;
      }
    }

    // Check if similar messages have been created within the time threshold.
    // If yes, then a new message should not be created.
    $threshold = $this->configFactory->get('eic_messages.settings')->get('notification_duplicate_threshold');
    try {
      if (!empty($this->checkDuplicateMessages($message, $threshold))) {
        return FALSE;
      }
    }
    catch (\Exception $e) {

    }

    return TRUE;
  }

  /**
   * Check for similar messages in certain timespan.
   *
   * @param Drupal\message\MessageInterface $message
   *   The message to be created.
   * @param int $threshold
   *   The timespan to look for older similar messages, in seconds.
   *
   * @return array|int
   *   And array of messages IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function checkDuplicateMessages(MessageInterface $message, int $threshold = 3600) {
    $request_time = $this->timeService->getRequestTime();

    // Look for similar older messages.
    $query = $this->entityTypeManager->getStorage('message')->getQuery();
    $query->condition('template', $message->getTemplate()->id());
    $query->condition('uid', $message->getOwnerId());
    $query->condition('created', ($request_time - $threshold), '>=');

    // Apply conditions based on the message template's primary keys.
    foreach ($this->getMessageTemplatePrimaryKeys($message->getTemplate()) as $primary_key) {
      // Avoid adding a condition if the field doesn't exist.
      if (!$message->hasField($primary_key)) {
        continue;
      }

      $query->condition($primary_key, $message->get($primary_key)->getValue()[0]);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageTemplatePrimaryKeys(MessageTemplateInterface $message_template) {
    // Get the message template type.
    $message_template_type = $message_template->getThirdPartySetting('eic_messages', 'message_template_type');

    switch ($message_template_type) {
      case MessageTemplateTypes::STREAM:
        return ActivityStreamMessageTemplates::getMessageTemplatePrimaryKeys($message_template);

      case MessageTemplateTypes::SUBSCRIPTION:
        return MessageSubscriptionTypes::getMessageTemplatePrimaryKeys($message_template);

    }

    return [];
  }

}
