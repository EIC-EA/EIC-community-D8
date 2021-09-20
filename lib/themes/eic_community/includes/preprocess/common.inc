<?php

/**
 * @file
 * Contains common helper functions.
 */

use Drupal\Core\Block\BlockPluginInterface;

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
