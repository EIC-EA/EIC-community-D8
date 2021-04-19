<?php

/**
 * @file
 * Functions to support theming.
 */

use Drupal\comment\Entity\Comment;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

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
        'author' => $comment_origin_author->getDisplayName(),
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
  // If a diff-date is needed, use this.
//  $createdAtDiff = $dateFormatter->formatDiff($comment->getCreatedTime(), Drupal\Core\Datetime\Entity\DateFormat::create());

  $variables['comment'] = [
    'author' => [
      'author' => $author->getDisplayName(),
      'path' => '',
      'image' => $author_image,
    ],
    'comment' => $comment->comment_body->value,
    'comment_id' => $comment->cid->value,
    'comment_origin' => $comment_origin,
    'is_owner' => $current_user->id() == $comment->getOwnerId(),
    'reply_path' => $comment_reply->toString(),
    'timestamp' => $created_at,
    'user' => $current_user,
    'icon_file_path' => $variables['eic_icon_path'],
  ];

  // Stored in render array as twig does some logic with it.
  // @TODO: Create Logic for role Content Administrator to overwrite "ownership"
  // @TODO: If there are replies to the comment, disable 'can_edit'.
  // @SEE: EICNET-639
  $variables['comment']['can_edit'] = $current_user->hasPermission('edit own comments');
  if ($variables['comment']['can_edit']) {
    $variables['comment']['edit_path'] = $comment->toUrl('edit-form');
    $variables['comment']['remove_path'] = $comment->toUrl('delete-form');
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
    'author' => $author->getDisplayName(),
    'path' => '',
    'image' => $author_image,
  ];
}
