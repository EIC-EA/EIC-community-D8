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
}
