<?php

namespace Drupal\eic_subscription_digest\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\Service\SubscriptionMessageChecker;
use Drupal\eic_search\Service\SolrSearchManager;
use Drupal\eic_subscription_digest\Constants\DigestCategories;
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
   * @var \Drupal\eic_search\Service\SolrSearchManager
   */
  private $searchManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_message_subscriptions\Service\SubscriptionMessageChecker $message_checker
   * @param \Drupal\eic_search\Service\SolrSearchManager $search_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SubscriptionMessageChecker $message_checker,
    SolrSearchManager $search_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageChecker = $message_checker;
    $this->searchManager = $search_manager;
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
      ->sort('created', 'DESC')
      ->execute();

    if (empty($messages)) {
      return [];
    }

    /** @var \Drupal\message\MessageInterface[] $messages */
    $messages = $this->entityTypeManager->getStorage('message')->loadMultiple($messages);
    $formatted_list = [];
    foreach ($messages as $message) {
      $formatted_item = $this->formatItem($message);
      if (!$formatted_item || !$formatted_item['category'] || !$formatted_item['entity']) {
        continue;
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $formatted_item['entity'];
      if (!$entity->access('view', $user)) {
        continue;
      }

      $formatted_list[$formatted_item['category']]['items'][] = $formatted_item;
    }

    return $this->sortItems($formatted_list);
  }

  /**
   * @param \Drupal\message\MessageInterface $message
   *
   * @return array
   */
  private function formatItem(MessageInterface $message): array {
    $template_id = $message->getTemplate()->id();
    if (!in_array($template_id, DigestSubscriptions::SUPPORTED_MESSAGES)) {
      return [];
    }

    switch ($template_id) {
      case MessageSubscriptionTypes::NODE_PUBLISHED:
        $formatted_item = [
          'entity' => $message->get('field_referenced_node')->entity,
          'category' => DigestCategories::NEWS_STORIES,
        ];
        break;
      case MessageSubscriptionTypes::NEW_COMMENT_REPLY:
      case MessageSubscriptionTypes::NEW_COMMENT:
        /** @var \Drupal\comment\CommentInterface $comment */
        $comment = $message->get('field_referenced_comment')->entity;
        $commented_entity = $comment->getCommentedEntity();
        $formatted_item = [
          'entity' => $commented_entity,
          'category' => $commented_entity instanceof GroupInterface
            ? $commented_entity->bundle()
            : DigestCategories::NEWS_STORIES,
        ];
        break;
      case MessageSubscriptionTypes::GROUP_CONTENT_UPDATED:
      case MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED:
      case MessageSubscriptionTypes::NEW_EVENT_PUBLISHED:
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $message->get('field_group_ref')->entity;
        $formatted_item = [
          'entity' => $group,
          'category' => $group->bundle(),
        ];
        break;
    }

    $formatted_item['message'] = $message;

    return $formatted_item;
  }

  /**
   * @param array $list
   *
   * @return array
   */
  private function sortItems(array $list): array {
    // List of entity types for which the activity score should exist.
    $activity_score_sorted = [DigestCategories::EVENT, DigestCategories::GROUP, DigestCategories::ORGANISATION];
    foreach ($list as $key => &$category) {
      if (!in_array($key, $activity_score_sorted)) {
        // By default, items are sorted on 'created'.
        continue;
      }

      foreach ($category['items'] as &$item) {
        // TODO Retrieve the activity score.
        $item['activity_score'] = 0;
      }

      usort($category['items'], function ($itemA, $itemB) {
        return $itemA['activity_score'] <=> $itemB['activity_score'];
      });
    }

    return $list;
  }

}
