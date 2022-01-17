<?php

/**
 * @file
 * Prepares variables for overview_page templates.
 */

use Drupal\eic_overviews\GlobalOverviewPages;

/**
 * Implements hook_preprocess_overview_page().
 */
function eic_community_preprocess_overview_page(array &$variables) {
  // Adds breadcrumbs block to the theme variables.
  $variables['eic_community_breadcrumbs'] = _eic_community_load_breadcrumb_block();
  // Adds overview page operations.
  $operations = \Drupal::service('eic_overviews.global_overview_pages')->getGlobalOverviewPageOperations(GlobalOverviewPages::GROUPS);
  if (!empty($operations)) {
    foreach ($operations as $operation) {
      $variables['overview_page_actions'][] = [
        'label' => $operation['title'],
        'path' => $operation['url'],
      ];
    }
  }
}
