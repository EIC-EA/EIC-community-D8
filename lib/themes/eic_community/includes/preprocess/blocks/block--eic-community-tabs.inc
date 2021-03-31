<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block__eic_community_tabs().
 */

/**
 * Implements hook_preprocess_block__eic_community_tabs().
 */
function eic_community_preprocess_block__eic_community_tabs(&$variables) {
  $variables['attributes']['class'][] = 'ecl-u-mb-2xl';
}
