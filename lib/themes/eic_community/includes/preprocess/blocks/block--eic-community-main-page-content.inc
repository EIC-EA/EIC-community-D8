<?php

/**
 * @file
 * Contains preprocessor for block__eic_community_main_page_content.
 */

/**
 * Implements hook_preprocess_block__eic_community_main_page_content().
 */
function eic_community_preprocess_block__eic_community_main_page_content(&$variables) {
  $variables['attributes']['class'][] = 'ecl-u-mb-2xl';
}
