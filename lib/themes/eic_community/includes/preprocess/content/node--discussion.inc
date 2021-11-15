<?php

/**
 * @file
 * Prepares variables for node discussion templates.
 */

use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\group\Entity\GroupInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_node() for discussion node.
 */
function eic_community_preprocess_node__discussion(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  $discussion_type = [
    'label' => $node->get('field_discussion_type')->getString(),
    'icon' => [
      'type' => 'custom',
      'name' => $node->get('field_discussion_type')->getString(),
    ],
  ];

  switch ($variables['view_mode']) {
    case 'full':
      _preprocess_discussion_full($variables, $discussion_type, $node);
      break;

    case 'teaser':
      _preprocess_discussion_teaser($variables, $discussion_type, $node);
      break;
  }
}

/**
 * Preprocesses full display.
 */
function _preprocess_discussion_full(&$variables, $discussion_type, $node) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  $variables['type'] = $discussion_type;
  if (!empty($variables['elements']['contributor_ids'])) {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadMultiple($variables['elements']['contributor_ids']);
    $contributors = ['items' => []];

    foreach ($users as $user) {
      $contributors['items'][] = eic_community_get_teaser_user_display($user);
    }

    $variables['contributors'] = $contributors;
  }

  $variables['author'] = eic_community_get_teaser_user_display($node->getOwner());
  $variables['timestamp']['label'] = eic_community_get_teaser_time_display($node->get('changed')->value);

  $flags = array_filter($variables['elements'], function ($key) use ($node) {
    // If element is not a flag we skip it.
    if (strpos($key, 'flag') === FALSE) {
      return FALSE;
    }

    return _eic_community_get_flag_access(str_replace('flag_', '', $key),
      $node);
  }, ARRAY_FILTER_USE_KEY);

  $flags = array_map(function ($item) {
    return ['content' => $item];
  }, $flags);

  $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
  if ($group instanceof GroupInterface) {
    $variables['share_group_content'] = _eic_community_get_share_group_content_link(
      $group,
      $node
    );
  }
  
  $variables['flags']['items'] = $flags;

  foreach ($variables['elements']['field_vocab_topics'] as $key => $value) {
    if (is_int($key)) {
      $value['#options']['attributes']['class'][] = 'ecl-tag';
      $value['#options']['attributes']['class'][] = 'ecl-featured-list__item-tag';
      $variables['elements']['field_vocab_topics'][$key] = $value;
    }
  }
}

/**
 * Preprocesses teaser display.
 */
function _preprocess_discussion_teaser(&$variables, $discussion_type, $node) {
  // @todo get comment count from backend.
  $comment_count = 0;

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
    'type' => $discussion_type,
    'author' => eic_community_get_teaser_user_display($node->getOwner()),
    'description' => Markup::create(Xss::filter($node->get('field_body')->value)),
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
      'comment' => $comment->get('comment_body')->value,
      'comment_id' => $last_comment_id,
      'author' => eic_community_get_teaser_user_display($comment->getOwner()),
      'timestamp' => eic_community_get_teaser_time_display($comment->getCreatedTime()),
    ];
  }

  $variables['discussion_item'] = $discussion_item;
}
