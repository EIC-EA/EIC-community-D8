<?php

/**
 * @file
 * Contains common helper functions.
 */

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityInterface;

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

  $user_flag = $flag_service->getFlagging($flag, $entity);

  // We need to create a fake flag if the user never flagged the content,
  // otherwise we can't do an access check.
  if (!$user_flag) {
    $user_flag = \Drupal::entityTypeManager()->getStorage('flagging')->create([
      'uid' => \Drupal::currentUser()->id(),
      'flag_id' => $flag->id(),
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'global' => $flag->isGlobal(),
    ]);
  }

  return $user_flag->access('view');
}
