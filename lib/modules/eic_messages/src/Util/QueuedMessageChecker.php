<?php

namespace Drupal\eic_messages\Util;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\message\MessageInterface;
use Drupal\message\MessageTemplateInterface;
use Exception;

class QueuedMessageChecker {

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
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    TimeInterface $date_time,
    ConfigFactory $config_factory,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->timeService = $date_time;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param \Drupal\message\MessageInterface $message
   *
   * @return bool
   */
  public function shouldCreateNewMessage(MessageInterface $message): bool {
    // Check if message is of type subscription or activity stream.
    // If it is not, then a new message should be created.
    $message_template_type = $message->getTemplate()
      ->getThirdPartySetting('eic_messages', 'message_template_type');
    $anti_spammed_types = [
      MessageTemplateTypes::SUBSCRIPTION,
      MessageTemplateTypes::STREAM,
    ];
    if (
      !in_array($message_template_type, $anti_spammed_types)
      || $message->getTemplate()->id() === ActivityStreamMessageTemplates::SHARE_CONTENT
    ) {
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
    $threshold = $this->configFactory->get('eic_messages.settings')
      ->get('notification_duplicate_threshold');
    try {
      if (!empty($this->checkDuplicateMessages($message, $threshold))) {
        return FALSE;
      }
    } catch (Exception $e) {
    }

    return TRUE;
  }

  /**
   * Check for similar messages in certain timespan.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to be created.
   * @param int $threshold
   *   The timespan to look for older similar messages, in seconds.
   *
   * @return array|int
   *   And array of messages IDs.
   */
  protected function checkDuplicateMessages(
    MessageInterface $message,
    int $threshold = 3600
  ) {
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

      $query->condition($primary_key,
        $message->get($primary_key)->getValue()[0]);
    }

    return $query->execute();
  }

  /**
   * @param \Drupal\message\MessageTemplateInterface $message_template
   *
   * @return string[]|false
   */
  public function getMessageTemplatePrimaryKeys(
    MessageTemplateInterface $message_template
  ) {
    // Get the message template type.
    $message_template_type = $message_template->getThirdPartySetting('eic_messages','message_template_type');

    switch ($message_template_type) {
      case MessageTemplateTypes::STREAM:
        return ActivityStreamMessageTemplates::getMessageTemplatePrimaryKeys($message_template);

      case MessageTemplateTypes::SUBSCRIPTION:
        return MessageSubscriptionTypes::getMessageTemplatePrimaryKeys($message_template);
    }

    return [];
  }

}
