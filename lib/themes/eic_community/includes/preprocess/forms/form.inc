<?php

/**
 * @file
 * Functions to support theming.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_form(&$variables) {
  if ($variables['element']['#id'] == 'comment-form') {
    _eic_community_preprocess_comment_form($variables);
  }

  // Preprocess views exposed form.
  if (strpos($variables['element']['#id'], 'views-exposed-form-') !== FALSE) {
    _eic_community_preprocess_views_exposed_form($variables);
  }
}
