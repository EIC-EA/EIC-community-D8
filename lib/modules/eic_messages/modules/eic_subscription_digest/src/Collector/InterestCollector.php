<?php

namespace Drupal\eic_subscription_digest\Collector;

use DateTimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_user\UserHelper;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * Class InterestCollector
 *
 * @package Drupal\eic_subscription_digest\Collectors
 */
class InterestCollector implements CollectorInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_user\UserHelper $user_helper
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UserHelper $user_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->userHelper = $user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(UserInterface $user, DateTimeInterface $start_date, DateTimeInterface $end_date): array {
    $supported_templates = [
      MessageSubscriptionTypes::NODE_PUBLISHED,
    ];

    $profile = $this->userHelper->getUserMemberProfile($user);
    $interest_topics = $profile->get('field_vocab_topic_interest')->referencedEntities();
    if (empty($interest_topics)) {
      return [];
    }

    $interest_topics = array_map(function (TermInterface $term) {
      return $term->id();
    }, $interest_topics);

    $message_ids = $this->entityTypeManager->getStorage('message')
      ->getQuery()
      ->condition('template', $supported_templates, 'IN')
      ->condition('field_topic_term', $interest_topics, 'IN')
      ->condition('created', [
        $start_date->getTimestamp(),
        $end_date->getTimestamp(),
      ], 'BETWEEN')
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->execute();

    if (empty($message_ids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('message')->loadMultiple($message_ids);
  }

}
