<?php

/**
 * @file
 * Contains common helper functions.
 */

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\eic_flags\FlagType;

/**
 * Returns the build of the social share block.
 *
 * @return array|bool
 *   The build array of the block or FALSE if not found.
 */
function _eic_community_get_social_share_block() {
  $share_block = \Drupal::service('plugin.manager.block')
    ->createInstance('social_share', []);

  if (!$share_block instanceof BlockPluginInterface) {
    return FALSE;
  }

  // Return the build array.
  $build = $share_block->build();
  $build['#title'] = t('Share');
  return $build;
}

/**
 * Gets the access to flag an entity.
 *
 * @param string $flag_id
 *   The flag ID.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The flaggable entity.
 *
 * @return bool
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

  return $flag->actionAccess($action);
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
 *
 * @return array
 *   An array suitable for the templates.
 */
function _eic_community_get_entity_stats(EntityInterface $entity) {
  $stats = [];
  foreach (\Drupal::service('eic_statistics.helper')->getEntityStatistics($entity) as $stat => $value) {
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

      case FlagType::LIKE_CONTENT:
        $item['label'] = t('Likes');
        $item['icon']['name'] = 'like';
        break;

    }

    $item['icon']['type'] = 'custom';
    $item['value'] = $value;
    $stats[$stat] = $item;
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
function _eic_community_get_entity_last_comment(EntityInterface $entity, $first_level_only = TRUE, $published_only = TRUE) {
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
