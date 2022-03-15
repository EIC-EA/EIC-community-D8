<?php

use Drupal\Core\Url;
use Drupal\message\MessageInterface;

/**
 * Implements hook_preprocess_message().
 */
function eic_community_preprocess_message(&$variables) {
  if ($variables['view_mode'] !== 'mail_body') {
    return;
  }
  
  $supported_templates = [
    'notify_user_tagged_on_comment' => function (MessageInterface $message) {
      /** @var \Drupal\node\NodeInterface $comment */
      $node = $message->get('field_referenced_node')->entity;
      /** @var \Drupal\comment\CommentInterface $comment */
      $comment = $message->get('field_referenced_comment')->entity;

      return [
        'view_comment' => [
          'label' => t('View comment'),
          'path' => Url::fromRoute(
            'entity.node.canonical',
            ['node' => $node->id(), 'highlighted-comment' => $comment->id()])->toString(),
          'type' => 'yellow',
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
