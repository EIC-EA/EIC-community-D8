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
  /** @var \Drupal\eic_overviews\GlobalOverviewPages $overview_helper */
  $overview_helper = \Drupal::service('eic_overviews.global_overview_pages');
  // Adds overview page operations.
  $overview_id = $overview_helper->getCurrentOverviewPageId();
  if (!$overview_id) {
    return;
  }

  $operations = $overview_helper->getGlobalOverviewPageOperations($overview_id);
  if (!empty($operations)) {
    if (count($operations) > 1) {
      $items = [];
      foreach($operations as $operation) {
        $items[] = [
          'link' => $operation
        ];
      }

      $variables['overview_page_actions'][] = [
        'label' => t('Create Content'),
        'items' => $items
      ];
    }
    else {
      $variables['overview_page_actions'][] = [
        'link' => reset($operations),
        'type' => 'cta',
      ];
    }
  }
}
