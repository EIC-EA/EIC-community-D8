<?php

/**
 * @file
 * Prepares variables for node video templates.
 */

/**
 * Implements hook_preprocess_node__bundle() for vide node.
 */
function eic_community_preprocess_node__video(array &$variables) {
  if ($variables['view_mode'] === 'full') {
    _eic_community_display_flags($variables);
    _eic_community_display_topics($variables);

    _eic_community_display_contributors($variables);

    // Provide the Social Share block.
    if ($share_block = _eic_community_get_social_share_block()) {
      $variables['social_share'] = $share_block;
    }
  }
}
