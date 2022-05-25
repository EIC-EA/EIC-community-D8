<?php

namespace Drupal\eic_subscription_digest\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_subscription_digest\Collector\CollectorInterface;
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

  use StringTranslationTrait;

  /**
   * @var \Drupal\eic_subscription_digest\Collector\CollectorInterface[]
   */
  private $collectors;

  /**
   * @param \Drupal\user\UserInterface $user
   * @param string $digest_type
   *
   * @return array
   * @throws \Exception
   */
  public function getList(UserInterface $user, string $digest_type): array {
    $grouped_messages = $this->collectMessages($user, $digest_type);
    if (empty($grouped_messages)) {
      return [];
    }

    $formatted_list = [
      DigestCategories::GROUP => [
        'label' => $this->t('Groups'),
      ],
      DigestCategories::EVENT => [
        'label' => $this->t('Events'),
      ],
      DigestCategories::ORGANISATION => [
        'label' => $this->t('Organisations'),
      ],
      DigestCategories::NEWS_STORIES => [
        'label' => $this->t('News & Stories'),
      ],
    ];
    foreach ($grouped_messages as $message) {
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
      if (!in_array($key, $activity_score_sorted) || empty($category['items'])) {
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

  /**
   * @param \Drupal\user\UserInterface $user
   * @param string $digest_type
   *
   * @return array
   * @throws \Exception
   */
  private function collectMessages(UserInterface $user, string $digest_type): array {
    $end_date = new \DateTime('now');
    $interval = DigestTypes::getInterval($digest_type);
    $start_date = (new \DateTime('now'))->sub($interval);

    $collected_messages = [];
    foreach ($this->collectors as $collector) {
      $collected_messages = $collected_messages + $collector->getMessages($user, $start_date, $end_date);
    }

    return $collected_messages;
  }

  /**
   * @param \Drupal\eic_subscription_digest\Collector\CollectorInterface $collector
   */
  public function addCollector(CollectorInterface $collector) {
    $this->collectors[] = $collector;
  }

}
