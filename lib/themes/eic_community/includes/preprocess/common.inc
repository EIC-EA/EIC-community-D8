<?php

/**
 * @file
 * Contains common helper functions.
 */

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\eic_flags\FlagType;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Returns the build of the social share block.
 *
 * @return array|bool
 *   The build array of the block or FALSE if not found.
 */
function _eic_community_get_social_share_block() {
  $social_share_json = [
    'service' => 'share',
    'popup' => TRUE,
    'selection' => TRUE,
    'to' => [
      'more',
      'twitter',
      'facebook',
      'linkedin',
      'e-mail',
    ],
    'stats' => TRUE,
  ];

  return [
    '#theme' => 'oe_webtools_social_share',
    '#title' => NULL,
    '#icons_json' => Markup::create(Json::encode($social_share_json)),
    '#attached' => ['library' => ['oe_webtools/drupal.webtools-smartloader']],
  ];
}

/**
 * Returns the build for the content share feature.
 *
 * @param \Drupal\group\Entity\GroupInterface $current_group
 *   The group entity we're sharing from.
 * @param \Drupal\node\NodeInterface $node
 *   The content entity we want to share.
 *
 * @return array
 *   The build array.
 */
function _eic_community_get_share_group_content_link(
  GroupInterface $current_group,
  NodeInterface $node
) {
  $account = \Drupal::currentUser();

  // Check if user has permission to share.
  if (!$current_group->hasPermission('share content between groups', $account)) {
    return [];
  }

  static $formatted_groups;
  /** @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorage $group_visibility_storage */
  $group_visibility_storage = \Drupal::service('oec_group_flex.group_visibility.storage');
  $group_visibility_entity = $group_visibility_storage->load($current_group->id());
  $visibility_type = $group_visibility_entity ?
    $group_visibility_entity->getType() :
    NULL;

  if ($visibility_type !== GroupVisibilityType::GROUP_VISIBILITY_PUBLIC) {
    return [];
  }

  if (empty($formatted_groups)) {
    $share_manager = \Drupal::service('eic_share_content.share_manager');
    foreach ($share_manager->getShareableTargetGroupsForUser($account, $current_group) as $group) {
      if (!isset($formatted_groups[$group->bundle()])) {
        $formatted_groups[$group->bundle()] = [
          'label' => $group->type->entity->label(),
          'entities' => [],
        ];
      }

      $formatted_groups[$group->bundle()]['entities'][] = [
        'id' => $group->id(),
        'label' => $group->label(),
      ];
    }
  }

  return [
    '#theme' => 'eic_share_content_link',
    '#groups' => $formatted_groups,
    '#endpoint' => Url::fromRoute('eic_share_content.share', [
      'group' => $current_group->id(),
      'node' => $node->id(),
    ], ['absolute' => TRUE])->toString(),
    '#attached' => [
      'drupalSettings' => [
        'share_modal' => [
          'translations' => [
            'maximum_character' => t('Your description can contain up to 300 characters.'),
          ],
        ],
      ],
    ],
  ];
}

/**
 * Gets the access to flag an entity.
 *
 * @param string $flag_id
 *   The flag ID.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The flaggable entity.
 *
 * @return bool|\Drupal\Core\Access\AccessResult
 *   TRUE if the user can flag the entity.
 */
function _eic_community_get_flag_access($flag_id, EntityInterface $entity) {
  /** @var \Drupal\flag\FlagService $flag_service */
  $flag_service = \Drupal::service('flag');

  // Load flag by ID.
  $flag = $flag_service->getFlagById($flag_id);

  // If flag does not exist, we skip it.
  if (!$flag) {
    return TRUE;
  }

  // Check if we have a flagging for this user and entity. If we have one we
  // check if the user can unflag, otherwise we check if the user can flag.
  $user_flag = $flag_service->getFlagging($flag, $entity);
  $action = $user_flag ? 'unflag' : 'flag';

  return $flag->actionAccess($action, NULL, $entity);
}

/**
 * Returns a render array for the given flag ID and entity..
 *
 * @param string $flag_id
 *   The flag ID.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The flaggable entity.
 *
 * @return array
 *   TRUE if the user can flag the entity.
 */
function _eic_community_get_flag_render_array($flag_id, EntityInterface $entity) {
  return [
    '#lazy_builder' => [
      'flag.link_builder:build',
      [
        $entity->getEntityTypeId(),
        $entity->id(),
        $flag_id,
      ],
    ],
    '#create_placeholder' => TRUE,
  ];
}

/**
 * Returns the statistics for a given entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which we return the statistics.
 * @param string[] $stat_types
 *   The statistics types to return. Will return all if empty.
 *   Can be following values:
 *   - views
 *   - downloads
 *   - comments
 *   - experts
 *   - organisations
 *   - FlagType::LIKE_CONTENT: see Drupal\eic_flags\FlagType.
 *
 * @return array
 *   An array suitable for the templates.
 */
function _eic_community_get_entity_stats(EntityInterface $entity, array $stat_types = []) {
  $stats = [];
  foreach (\Drupal::service('eic_statistics.helper')->getEntityStatistics($entity) as $stat => $value) {

    // Skip this stat if it is not required.
    if (!empty($stat_types) && !in_array($stat, $stat_types)) {
      continue;
    }

    $item = [];
    switch ($stat) {
      case 'views':
        $item['label'] = t('Views');
        $item['icon']['name'] = 'views';
        break;

      case 'downloads':
        $item['label'] = t('Downloads');
        $item['icon']['name'] = 'download';
        break;

      case 'comments':
        $item['label'] = t('Comments');
        $item['icon']['name'] = 'comment';
        break;

      case 'experts':
        $item['label'] = t('Experts');
        $item['icon']['name'] = 'user_circle';
        break;

      case 'organisations':
        $item['label'] = t('Organisations');
        $item['icon']['name'] = 'company';
        break;

      case FlagType::LIKE_CONTENT:
        $item['label'] = t('Likes');
        $item['icon']['name'] = 'like';
        break;
    }

    $item['icon']['type'] = 'custom';
    $item['value'] = $value ?: 0;
    $stats[$stat] = $item;
  }

  // If comment system is not open on the entity, do not show comments stats.
  if (
    array_key_exists('comments', $stats) &&
    $entity->hasField('field_comments')
  ) {
    $field_comments = $entity->get('field_comments')->first()->getValue();
    $status = intval($field_comments['status']);

    if ($status !== CommentItemInterface::OPEN) {
      unset($stats['comments']);
    }
  }

  return $stats;
}

/**
 * Returns the last comment object for the given entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param bool $first_level_only
 *   Whether to return first level comments only. Defaults to TRUE.
 * @param bool $published_only
 *   Whether to return published comments only. Defaults to TRUE.
 *
 * @return \Drupal\comment\CommentInterface|null
 *   The comment object of NULL if not found.
 */
function _eic_community_get_entity_last_comment(
  EntityInterface $entity,
  $first_level_only = TRUE,
  $published_only = TRUE
) {
  $query = \Drupal::entityQuery('comment')
    ->condition('entity_id', $entity->id())
    ->condition('entity_type', $entity->getEntityTypeId());

  if ($published_only) {
    $query->condition('status', CommentInterface::PUBLISHED);
  }

  if ($first_level_only) {
    $query->condition('pid', NULL, 'IS NULL');
  }

  $query->sort('created', 'DESC')->range(0, 1);
  $last_comment_id = $query->execute();

  if ($last_comment_id) {
    $last_comment_id = array_shift($last_comment_id);
    return Comment::load($last_comment_id);
  }

  return NULL;
}

/**
 * Loads taxonomy term parents from a term.
 *
 * @param \Drupal\taxonomy\TermInterface $term
 *   The taxonomy term from which we want to load the parents.
 * @param array $loaded_parents
 *   The array of loaded parents.
 *
 * @return array
 *   Array of all loaded parents of the term.
 */
function _eic_community_get_term_parents(TermInterface $term, array $loaded_parents) {
  if ($term->parent->entity) {
    $loaded_parents[$term->parent->entity->id()] = $term->parent->entity;
    $loaded_parents = _eic_community_get_term_parents($term->parent->entity, $loaded_parents);
  }
  return $loaded_parents;
}

/**
 * Processes all term parents hierarchically.
 *
 * @param \Drupal\taxonomy\TermInterface $term
 *   The taxonomy term from which we want load the hierarchy.
 * @param array $terms
 *   The hierarchical tree of terms.
 * @param array $loaded_parents
 *   The array of loaded parents.
 *
 * @return array
 *   Nested array of all loaded parents of the term.
 */
function _eic_community_process_term_parents_tree(TermInterface $term, array $terms, array $loaded_parents) {
  $parent = array_shift($loaded_parents);
  if (!isset($terms[$parent->id()])) {
    $terms[$parent->id()]['term'] = $parent;
    $terms[$parent->id()]['title'] = $parent->getName();
    $terms[$parent->id()]['items'] = [];
  }

  if (!empty($loaded_parents)) {
    $terms[$parent->id()]['items'] = _eic_community_process_term_parents_tree(
      $term,
      $terms[$parent->id()]['items'],
      $loaded_parents
    );
  }
  else {
    $terms[$parent->id()]['items'][$term->id()]['term'] = $term;
    $terms[$parent->id()]['items'][$term->id()]['title'] = $term->getName();
  }

  return $terms;
}

/**
 * Loads breadcrumb block to use it in twig templates.
 *
 * @return array
 *   The renderable array of the block.
 */
function _eic_community_load_breadcrumb_block() {
  $block_manager = \Drupal::service('plugin.manager.block');
  // You can hard code configuration or you load from settings.
  $config = [];
  $plugin_block = $block_manager->createInstance('system_breadcrumb_block', $config);
  // Some blocks might implement access check.
  $access_result = $plugin_block->access(\Drupal::currentUser());
  // Return empty render array if user doesn't have access.
  // $access_result can be boolean or an AccessResult class.
  if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
    // You might need to add some cache tags/contexts.
    return [];
  }
  $render = $plugin_block->build();
  // In some cases, you need to add the cache tags/context depending on
  // the block implemention. As it's possible to add the cache tags and
  // contexts in the render method and in ::getCacheTags and
  // ::getCacheContexts methods.
  return $render;
}
