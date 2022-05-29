<?php

use Drupal\comment\CommentInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_user\UserHelper;
use Drupal\message\MessageInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Implements hook_preprocess_message().
 */
function eic_community_preprocess_message(&$variables) {
  if ($variables['view_mode'] === 'notify_digest') {
    _eic_community_preprocess_digest_message($variables);
  }

  if ($variables['view_mode'] !== 'mail_body') {
    return;
  }

  $supported_templates = [
    'notify_user_tagged_on_comment' => function (MessageInterface $message) {
      /** @var \Drupal\node\NodeInterface $comment */
      $node = $message->get('field_referenced_node')->entity;
      /** @var \Drupal\comment\CommentInterface $comment */
      $comment = $message->get('field_referenced_comment')->entity;
      if (!$node instanceof NodeInterface || !$comment instanceof CommentInterface) {
        return [];
      }

      return [
        'view_comment' => [
          'label' => t('View comment'),
          'path' => Url::fromRoute(
            'entity.node.canonical',
            ['node' => $node->id(), 'highlighted-comment' => $comment->id()])->toString(),
          'attributes' => new Attribute(['class' => ['cta__yellow', 'cta__centered']]),
        ],
      ];
    },
  ];

  $message = $variables['message'];
  if (!$message instanceof MessageInterface
    || !array_key_exists($message->bundle(), $supported_templates)
  ) {
    return;
  }

  $additional_links = call_user_func($supported_templates[$message->bundle()], $message);
  if (empty($additional_links)) {
    return;
  }

  $variables['additional_links'] = $additional_links;
}

function eic_community_preprocess_eic_subscription_digest_mail(&$variables) {
  $user = $variables['user'];
  $variables['content'] = [
    'target_user' => [
      'full_name' => \Drupal::service('eic_user.helper')->getFullName($user),
    ],
  ];
}

/**
 * Preprocess function for messages displayed in the digest email.
 *
 * @param $variables
 *
 * @return void
 */
function _eic_community_preprocess_digest_message(&$variables) {
  /** @var MessageInterface $message */
  $message = $variables['message'];
  $uid = $message->getOwnerId();
  $user = User::load($uid);

  /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
  $entity = NULL;
  $action = NULL;
  $template_id = $message->getTemplate()->id();
  switch ($template_id) {
    case MessageSubscriptionTypes::NODE_PUBLISHED:
    case MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED:
      $entity = $message->get('field_referenced_node')->entity;
      $action = 'created';
      break;
    case MessageSubscriptionTypes::NEW_COMMENT_REPLY:
    case MessageSubscriptionTypes::NEW_COMMENT:
      /** @var \Drupal\comment\CommentInterface $comment */
      $entity = $message->get('field_referenced_comment')->entity;
      $action = $template_id === MessageSubscriptionTypes::NEW_COMMENT_REPLY ? 'replied' : 'commented';
      break;
    case MessageSubscriptionTypes::NEW_EVENT_PUBLISHED:
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $entity = $message->get('field_group_ref')->entity;
      $action = 'created';
      break;
    case MessageSubscriptionTypes::GROUP_CONTENT_UPDATED:
      $entity = $message->get('field_group_ref')->entity;
      $action = 'updated';
      break;
  }

  $variables['content'] = [
    'user' => [
      'full_name' => \Drupal::service('eic_user.helper')->getFullName($user),
      'profile_picture' => UserHelper::getUserAvatar($user),
    ],
    'action' => t($action),
    'entity' => $entity,
    'label' => $entity->label(),
    'entity_url' => $entity->toUrl()->toString(),
    'rendered_entity' => \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, 'mail_teaser'),
  ];
}
