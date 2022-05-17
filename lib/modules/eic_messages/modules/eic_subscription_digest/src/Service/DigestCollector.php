<?php

namespace Drupal\eic_subscription_digest\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\Service\SubscriptionMessageChecker;
use Drupal\eic_subscription_digest\Constants\DigestSubscriptions;
use Drupal\eic_subscription_digest\Constants\DigestTypes;
use Drupal\group\Entity\GroupInterface;
use Drupal\message\MessageInterface;
use Drupal\user\UserInterface;

/**
 * Class DigestCollector
 *
 * @package Drupal\eic_subscription_digest\Service
 */
class DigestCollector {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\eic_message_subscriptions\Service\SubscriptionMessageChecker
   */
  private $messageChecker;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_message_subscriptions\Service\SubscriptionMessageChecker $message_checker
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SubscriptionMessageChecker $message_checker
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageChecker = $message_checker;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   * @param string $digest_type
   *
   * @return array
   * @throws \Exception
   */
  public function getList(UserInterface $user, string $digest_type): array {
    $end_date = new \DateTime('now');
    $interval = DigestTypes::getInterval($digest_type);
    $start_date = (new \DateTime('now'))->sub($interval);

    $messages = $this->entityTypeManager->getStorage('message')
      ->getQuery()
      ->condition('template', DigestSubscriptions::SUPPORTED_MESSAGES, 'IN')
      ->condition('created', [
        $start_date->getTimestamp(),
        $end_date->getTimestamp(),
      ], 'BETWEEN')
      ->execute();

    if (empty($messages)) {
      return [];
    }

    /** @var \Drupal\message\MessageInterface[] $messages */
    $messages = $this->entityTypeManager->getStorage('message')->loadMultiple($messages);
    $formatted_list = [];
    foreach ($messages as $message) {
      if (!$this->messageChecker->shouldSend($user->id(), $message)) {
        continue;
      }
      
      $category = $this->getItemCategory($message);
      if (!$category) {
        continue;
      }

      $formatted_list[$category][] = $message;
    }

    return $formatted_list;
  }

  /**
   * @param \Drupal\message\MessageInterface $message
   *
   * @return string|null
   */
  private function getItemCategory(MessageInterface $message): ?string {
    $template_id = $message->getTemplate()->id();
    if (!in_array($template_id, DigestSubscriptions::SUPPORTED_MESSAGES)) {
      return NULL;
    }

    $category = NULL;
    switch ($template_id) {
      case MessageSubscriptionTypes::NODE_PUBLISHED:
        $category = 'news_stories';
        break;
      case MessageSubscriptionTypes::NEW_EVENT_PUBLISHED:
        $category = 'event';
        break;
      case MessageSubscriptionTypes::NEW_COMMENT_REPLY:
      case MessageSubscriptionTypes::NEW_COMMENT:
        /** @var \Drupal\comment\CommentInterface $comment */
        $comment = $message->get('field_referenced_comment')->entity;
        $commented_entity = $comment->getCommentedEntity();
        $category = $commented_entity instanceof GroupInterface ? $commented_entity->bundle() : 'news_stories';
        break;
      case MessageSubscriptionTypes::GROUP_CONTENT_UPDATED:
      case MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED:
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $message->get('field_group_ref')->entity;
        $category = $group->bundle();
        break;
    }

    return $category;
  }

}
