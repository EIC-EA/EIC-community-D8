<?php

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityInterface;
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

  switch ($message->bundle()) {
    case 'notify_content_recommendation':
      _eic_community_preprocess_message_receiver_name($variables);
      break;

  }

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
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _eic_community_preprocess_digest_message(&$variables) {
  /** @var MessageInterface $message */
  $message = $variables['message'];
  $uid = $message->getOwnerId();
  $user = User::load($uid);

  /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
  $entity = NULL;
  $action = NULL;
  $group_suffix = NULL;
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
      $entity = $message->get('field_referenced_node')->entity;
      $action = 'edited';
      break;
    case MessageSubscriptionTypes::GROUP_CONTENT_SHARED:
      $entity = $message->get('field_referenced_node')->entity;
      $action = 'shared';
      break;
  }

  if (!$entity instanceof EntityInterface) {
    return;
  }

  if (in_array(
    $template_id,
    [
      MessageSubscriptionTypes::NEW_GROUP_CONTENT_PUBLISHED,
      MessageSubscriptionTypes::GROUP_CONTENT_UPDATED,
      MessageSubscriptionTypes::GROUP_CONTENT_SHARED,
    ]
  )) {
    $group = $message->get('field_group_ref')->entity;
    $group_suffix = [
      'label' => $group->label(),
      'url' => $group->toUrl()->toString(),
      'type' => strtolower($group->type->entity->label()),
      'prefix' => $template_id === MessageSubscriptionTypes::GROUP_CONTENT_SHARED ? t('to') : t('in'),
    ];
  }

  $vowels = ['a', 'e', 'i', 'o', 'u'];
  $suffix = in_array(substr($entity->bundle(), 0, 1), $vowels) ? 'an' : 'a';
  $action = $action . ' ' . $suffix;
  $default_avatar = $variables['eic_mail_images_path'] . '/user_circle.png';

  $variables['content'] = [
    'user' => [
      'full_name' => \Drupal::service('eic_user.helper')->getFullName($user),
      'profile_picture' => !$user->get('field_media')->isEmpty() ? UserHelper::getUserAvatar($user) : $default_avatar,
      'url' => $user->toUrl()->toString(),
    ],
    'group_suffix' => $group_suffix,
    'action' => t($action),
    'entity' => $entity,
    'entity_type' => strtolower($entity->type->entity->label()),
    'label' => $entity->label(),
    'entity_url' => $entity->toUrl()->toString(),
    'rendered_entity' => \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, 'mail_teaser'),
  ];
}

/**
 * Adds user display name to the message body from field_receiver_email.
 */
function _eic_community_preprocess_message_receiver_name(&$variables) {
  $message = $variables['message'];

  if (!$message->hasField('field_receiver_email')) {
    return;
  }

  if ($receiver_email = $message->get('field_receiver_email')->value) {
    /** @var \Drupal\user\UserInterface $user */
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
          'mail' => $receiver_email,
        ]
      );
    $intro_text = t('Dear');
    if ($users) {
      $user = reset($users);
      $intro_text = t('Dear @user_full_name', ['@user_full_name' => $user->getDisplayName()]);
    }
    $variables['content']['partial_1']['#markup'] = "<p>$intro_text,</p>" . $variables['content']['partial_1']['#markup'];
  }
}
