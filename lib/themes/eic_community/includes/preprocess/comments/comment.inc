<?php

/**
 * @file
 * Functions to support theming.
 */

use Drupal\comment\Entity\Comment;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_comment(&$variables) {
  $author = User::load($variables['author_id']);
  $current_user = $variables['user'];
  $author_image = _get_profile_image_array($author);
  $comment = $variables['comment'];

  $comment_origin = NULL;
  if ($comment->pid->target_id !== NULL) {
    $comment_origin_entity = Comment::load($comment->pid->target_id);
    $comment_origin_author = User::load($comment_origin_entity->getOwnerId());
    $comment_origin = [
      'author' => [
        'name' => $comment_origin_author->getDisplayName(),
        'path' => '',
        'image' => _get_profile_image_array($comment_origin_author),
      ],
      'comment_id' => $comment_origin_entity->id(),
      'is_owner' => $current_user->id() == $comment_origin_entity->getOwnerId(),
    ];
  }

  $comment_reply = Url::fromRoute('comment.reply', [
    'entity_type' => $comment->getCommentedEntityTypeId(),
    'entity' => $comment->getCommentedEntityId(),
    'field_name' => $comment->getFieldName(),
    'pid' => $comment->id(),
  ]);

  $date_formatter = \Drupal::service('date.formatter');
  $created_at = $date_formatter->format($comment->getCreatedTime(), 'short');
  $attachments = [];
  $comment_attachments = $comment->get('field_comment_attachment')->referencedEntities();

  foreach ($comment_attachments as $comment_attachment) {
    if ($comment_attachment->bundle() === 'eic_document') {
      $file = File::load($comment_attachment->field_media_file->target_id);

      $attachments[] = [
        'title' => $comment_attachment->getName(),
        'path' => $file->createFileUrl(),
        'icon' => [
          'type' => 'custom',
          'name' => substr(strrchr($file->get('filemime')->getString(), '/'), 1),
        ],
      ];
    }
    else {
      $file = File::load($comment_attachment->oe_media_image->target_id);

      $attachments[] = [
        'title' => $comment_attachment->getName(),
        'path' => $file->createFileUrl(),
        'type' => $file->getMimeType(),
        'image' => [
          'src' => ImageStyle::load('thumbnail')->buildUrl($file->getFileUri()),
        ],
      ];
    }
  }

  $variables['comment'] = [
    'author' => [
      'name' => $author->getDisplayName(),
      'path' => '',
      'image' => $author_image,
    ],
    'comment' => $comment->comment_body->value,
    'comment_id' => $comment->cid->value,
    'comment_origin' => $comment_origin,
    'is_owner' => $current_user->id() == $comment->getOwnerId(),
    'timestamp' => $created_at,
    'user' => $current_user,
    'icon_file_path' => $variables['eic_icon_path'],
    'attachments' => $attachments,
  ];

  // If user can reply to comments we add the comment reply path to the theme
  // variables.
  if ($comment_reply->access($current_user)) {
    $variables['comment']['reply_path'] = $comment_reply->toString();
  }

  $operations = \Drupal::entityTypeManager()->getListBuilder('comment')->getOperations($comment);

  foreach ($operations as $key => $operation) {
    $variables['comment']['can_' . $key . '_comment'] = TRUE;
    $variables['comment'][$key . '_path'] = $operation['url']->toString();
  }

  $flag_link_builder = \Drupal::service('flag.link_builder');
  $link = $flag_link_builder->build('comment', $comment->id(), 'like_comment');

  $variables['comment']['flag_link'] = $link;

  // Add tagged users.
  if (!$comment->get('field_tagged_users')->isEmpty()) {
    /** @var \Drupal\user\UserInterface[] $tagged_users */
    $tagged_users = $comment->get('field_tagged_users')->referencedEntities();
    foreach ($tagged_users as $tagged_user) {
      $variables['comment']['tagged_users'][] = [
        'name' => $tagged_user->getDisplayName(),
        'path' => $tagged_user->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ];
    }
  }

  $view_mode = $variables['elements']['#view_mode'];

  switch ($view_mode) {
    case 'notification_teaser':
      $variables['comment']['author']['image'] = [
        'src' => $variables['eic_images_path'] . '/custom/svg/custom/user_circle.svg',
        'alt' => $author->getDisplayName(),
      ];
      // Removes unneeded variables from notification teaser.
      unset($variables['comment']['timestamp']);
      unset($variables['comment']['is_owner']);
      unset($variables['comment']['user']);
      break;

  }

}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Through forms/form.inc.
 */
function _eic_community_preprocess_comment_form(&$variables) {
  $author = User::load($variables['user']->id());
  $author_image = _get_profile_image_array($author);

  $variables['comment_form']['author'] = [
    'name' => $author->getDisplayName(),
    'path' => '',
    'image' => $author_image,
  ];
}
