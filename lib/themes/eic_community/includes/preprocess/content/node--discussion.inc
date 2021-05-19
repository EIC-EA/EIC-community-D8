<?php

/**
 * @file
 * Prepares variables for node discussion templates.
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\comment\Entity\Comment;

/**
 * Implements hook_preprocess_node() for discussion node.
 */
function eic_community_preprocess_node__discussion(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  if ($variables['teaser']) {
    // @todo get comment count from backend.
    $comment_count = 0;
    $discussion_type = $node->get('field_discussion_type')->getString();

    $tags = [];

    if (!$node->get('field_vocab_topics')->isEmpty()) {
      $topic_term_ids = array_map(function ($term) {
        return $term['target_id'];
      }, $node->get('field_vocab_topics')->getValue());
      $topic_terms = Term::loadMultiple($topic_term_ids);

      foreach ($topic_terms as $topic_term) {
        $tags[] = [
          'label' => $topic_term->get('name')->getString(),
          'path' => $topic_term->toUrl(),
        ];
      }
    }

    $discussion_item = [
      'type' => [
        'label' => $discussion_type,
        'icon' => [
          'type' => 'custom',
          'name' => $discussion_type,
        ],
      ],
      'author' => eic_community_get_teaser_user_display($node->getOwner()),
      'description' => $node->get('field_body')->first()->getValue()['value'],
      'tags' => $tags,
      'highlight' => FALSE,
      'timestamp' => [
        'label' => eic_community_get_teaser_time_display($node->get('changed')->value),
      ],
      'stats' => [
        [
          'value' => $comment_count,
          'icon' => [
            'type' => 'custom',
            'name' => 'comment',
          ],
        ],
      ],
    ];

    if ((int) $comment_count > 0) {
      $last_comment_id = \Drupal::entityQuery('comment')
        ->condition('entity_id', $node->id())
        ->condition('entity_type', 'node')
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();
      $last_comment_id = array_shift($last_comment_id);

      $comment = Comment::load($last_comment_id);

      $discussion_item['featured'] = [
        'comment' => $comment->get('comment_body')->first()->getValue()['value'],
        'comment_id' => $last_comment_id,
        'author' => eic_community_get_teaser_user_display($comment->getOwner()),
        'timestamp' => eic_community_get_teaser_time_display($comment->getCreatedTime()),
      ];
    }

    $variables['discussion_item'] = $discussion_item;
  }
}
