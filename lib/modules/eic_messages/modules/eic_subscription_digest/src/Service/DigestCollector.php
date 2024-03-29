<?php

namespace Drupal\eic_subscription_digest\Service;

use DateTime;
use Drupal\Core\Entity\ContentEntityInterface;
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
   * @param \DateTime|NULL $end_date
   *
   * @return array
   * @throws \Exception
   */
  public function getList(
    UserInterface $user,
    string $digest_type,
    DateTime $end_date = NULL
  ): array {
    if (!$end_date instanceof \DateTime) {
      $end_date = new \DateTime('now');
    }

    $interval = DigestTypes::getInterval($digest_type);
    $start_date = (clone $end_date)->sub($interval);
    $grouped_messages = $this->collectMessages($user, $digest_type, $start_date, $end_date);
    if (empty($grouped_messages)) {
      return [];
    }

    $formatted_list = [
      DigestCategories::GROUP => [
        'label' => $this->t('Your groups'),
        'icon' => 'groups',
      ],
      DigestCategories::EVENT => [
        'label' => $this->t('Your events'),
        'icon' => 'events',
      ],
      DigestCategories::ORGANISATION => [
        'label' => $this->t('Your organisations'),
        'icon' => 'organisations',
      ],
      DigestCategories::NEWS_STORIES => [
        'label' => $this->t('News and stories'),
        'icon' => 'news_stories',
      ],
    ];

    foreach ($grouped_messages as $message) {
      $formatted_item = $this->formatItem($message);
      if (!$formatted_item || !$formatted_item['category'] || !$formatted_item['entity']) {
        continue;
      }

      $category = $formatted_item['category'];
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $formatted_item['entity'];
      if (!$entity->access('view', $user)) {
        continue;
      }

      $formatted_list[$category]['items'][] = $formatted_item;
    }

    $formatted_list = $this->sortItems($formatted_list);
    foreach ($formatted_list as &$list) {
      if (empty($list['items']) || count($list['items']) <= 3) {
        continue;
      }

      $list['items'] = array_slice($list['items'], 0, 3);
    }

    return $formatted_list;
  }

  /**
   * @param \Drupal\eic_subscription_digest\Collector\CollectorInterface $collector
   */
  public function addCollector(CollectorInterface $collector) {
    $this->collectors[] = $collector;
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

    $content_validation_fields = [
      'field_group_ref',
      'field_referenced_node',
    ];

    $isValid = FALSE;
    foreach ($content_validation_fields as $field) {
      if ($message->hasField($field) && $message->get($field)->entity instanceof ContentEntityInterface) {
        $isValid = TRUE;
      }
    }

    if (!$isValid) {
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
      case MessageSubscriptionTypes::GROUP_CONTENT_SHARED:
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
  private
  function sortItems(
    array $list
  ): array {
    foreach ($list as &$category) {
      if (empty($category['items'])) {
        continue;
      }

      usort($category['items'], function ($itemA, $itemB) {
        if ($itemA['message']->hasField('field_referenced_node') && $itemB['message']->hasField('field_referenced_node')) {
          return $itemB['message']->get('field_referenced_node')->entity->getCreatedTime() <=> $itemA['message']->get('field_referenced_node')->entity->getCreatedTime();
        }

        return $itemB['message']->getCreatedTime() <=> $itemA['message']->getCreatedTime();
      });
    }

    return $list;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   * @param string $digest_type
   * @param \DateTime $start_date
   * @param \DateTime $end_date
   *
   * @return array
   */
  private
  function collectMessages(
    UserInterface $user,
    string $digest_type,
    DateTime $start_date,
    DateTime $end_date
  ): array {
    $collected_messages = [];
    foreach ($this->collectors as $collector) {
      $collected_messages = $collected_messages + $collector->getMessages($user, $start_date, $end_date);
    }

    return $collected_messages;
  }

}
