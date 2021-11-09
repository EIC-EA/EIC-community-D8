<?php

/**
 * @file
 * Prepares variables for node video templates.
 */

/**
 * Implements hook_preprocess_node__bundle() for vide node.
 */
function eic_community_preprocess_node__video(array &$variables) {
  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_display_flags($variables);
      _eic_community_display_topics($variables);
      _eic_community_display_document_contributors($variables);
      $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();
      break;
  }
}
