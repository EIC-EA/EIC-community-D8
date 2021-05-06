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

  // @TODO: Add permission checks with Group Implementation.
  // @see: EICNET-728.

  // If there are replies to the comment, disable 'can_edit'.
  $cid = $comment->id();
  $nid = $comment->getCommentedEntityId();
  $entity_type = $comment->getCommentedEntityTypeId();
  $num_of_replies = intval(comment_reply_count($cid, $nid, $entity_type));

  // Stored in render array as twig does some logic with it.
  $variables['comment']['can_edit_comment'] =
    (
      $current_user->hasPermission('edit own comments')
      && $current_user->id() == $comment->getOwnerId()
      && $num_of_replies === 0
    );

  $variables['comment']['can_remove_comment'] =
    $variables['comment']['can_edit_comment']
    || (
      $current_user->hasPermission('administer comments')
      && $num_of_replies === 0
    );

  if ($variables['comment']['can_edit_comment']) {
    $variables['comment']['edit_path'] = $comment->toUrl('edit-form');
  }

  if ($variables['comment']['can_remove_comment']) {
    $variables['comment']['remove_path'] = $comment->toUrl('delete-form');
  }

  $flag_link_builder = \Drupal::service('flag.link_builder');
  $link = $flag_link_builder->build('comment', $cid, 'like_comment');

  $variables['comment']['flag_link'] = $link;
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

/**
 * Helper function to fetch Comment reply count.
 * Ripped from the module comment_reply_count.
 */
function comment_reply_count($cid, $nid, $entity_type) {
  $result = \Drupal::entityQuery('comment')
    ->condition('entity_type', $entity_type)
    ->condition('entity_id', $nid)
    ->condition('pid', $cid)
    ->count()
    ->execute();
  return $result;
}
