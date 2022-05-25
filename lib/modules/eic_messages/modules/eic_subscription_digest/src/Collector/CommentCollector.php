<?php

namespace Drupal\eic_subscription_digest\Collector;

use DateTimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_flags\FlagType;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\user\UserInterface;

/**
 * Class CommentCollector
 *
 * @package Drupal\eic_subscription_digest\Collectors
 */
class CommentCollector implements CollectorInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(UserInterface $user, DateTimeInterface $start_date, DateTimeInterface $end_date): array {
    $supported_templates = [
      MessageSubscriptionTypes::NEW_COMMENT_REPLY,
      MessageSubscriptionTypes::NEW_COMMENT,
    ];

    $flag_ids = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('flag_id', FlagType::FOLLOW_CONTENT)
      ->condition('uid', $user->id())
      ->execute();

    if (empty($flag_ids)) {
      return [];
    }

    $follow_flags = $this->entityTypeManager->getStorage('flagging')->loadMultiple($flag_ids);
    $entity_ids = [];
    foreach ($follow_flags as $flagging) {
      $entity_ids[] = $flagging->get('entity_id')->value;
    }

    $message_ids = $this->entityTypeManager->getStorage('message')
      ->getQuery()
      ->condition('template', $supported_templates, 'IN')
      ->condition('field_referenced_node', $entity_ids, 'IN')
      ->condition('created', [
        $start_date->getTimestamp(),
        $end_date->getTimestamp(),
      ], 'BETWEEN')
      ->sort('created', 'DESC')
      ->execute();

    if (empty($message_ids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('message')->loadMultiple($message_ids);
  }

}
