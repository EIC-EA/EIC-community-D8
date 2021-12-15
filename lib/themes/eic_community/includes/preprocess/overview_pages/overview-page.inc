<?php

/**
 * @file
 * Prepares variables for overview_page templates.
 */

/**
 * Implements hook_preprocess_overview_page().
 */
function eic_community_preprocess_overview_page(array &$variables) {
  // Adds breadcrumbs block to the theme variables.
  $variables['eic_community_breadcrumbs'] = _eic_community_load_breadcrumb_block();
}
