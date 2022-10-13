<?php

/**
 * @file
 * Prepares variables for node discussion templates.
 */

use Drupal\comment\CommentInterface;
use Drupal\Component\Utility\Xss;
use Drupal\group\Entity\GroupInterface;
use Drupal\eic_comments\CommentsHelper;

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

    case 'mail_teaser':
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
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($variables['elements']['contributor_ids']);
    $contributors = ['items' => []];

    foreach ($users as $user) {
      $contributors['items'][] = eic_community_get_teaser_user_display($user, 'crop_80x80');
    }

    $variables['contributors'] = $contributors;
  }

  $variables['author'] = eic_community_get_teaser_user_display($node->getOwner(), 'crop_80x80');
  $variables['timestamp']['label'] = eic_community_get_teaser_time_display($node->get('changed')->value);

  $flags = array_filter($variables['elements'], function ($key) use ($node) {
    // If element is not a flag we skip it.
    if (strpos($key, 'flag') === FALSE) {
      return FALSE;
    }

    return _eic_community_get_flag_access(str_replace('flag_', '', $key), $node);
  }, ARRAY_FILTER_USE_KEY);

  $flags = array_map(function ($item) {
    return ['content' => $item];
  }, $flags);

  // Show recommend content flag in the 2nd place.
  if (isset($variables['elements']['recommend_content'])) {
    array_splice($flags, 2, 0, [$variables['elements']['recommend_content']]);
  }

  $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
  if ($group instanceof GroupInterface) {
    $flags['share_group_content']['content'] = _eic_community_get_share_group_content_link(
      $group,
      $node
    );

    $variables['#cache']['tags'] = array_merge($variables['#cache']['tags'], $group->getCacheTags());
    if (_eic_community_can_use_social_share($node)) {
      $flags['social_share']['content'] = _eic_community_get_social_share_block();
    }
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
  $teaser = _eic_community_prepare_node_teaser_array($node);
  $teaser['description'] = Xss::filter($node->get('field_body')->value);
  $teaser['type'] = $discussion_type;

  // Remove unwanted items.
  if (!empty($teaser['stats']['views'])) {
    unset($teaser['stats']['views']);
  }
  if (!empty($teaser['stats']['downloads'])) {
    unset($teaser['stats']['downloads']);
  }

  // Add last comment if there is one.
  if (
    ($last_comment = _eic_community_get_entity_last_comment($node)) &&
    $last_comment instanceof CommentInterface &&
    !\Drupal::currentUser()->isAnonymous()
  ) {
    $teaser['featured'] = [
      'comment' => CommentsHelper::formatHtmlComment($last_comment->get('comment_body')->value),
      'comment_id' => $last_comment->id(),
      'author' => eic_community_get_teaser_user_display($last_comment->getOwner()),
      'timestamp' => eic_community_get_teaser_time_display($last_comment->getCreatedTime()),
    ];
  }

  $variables['discussion_item'] = $teaser;
}
