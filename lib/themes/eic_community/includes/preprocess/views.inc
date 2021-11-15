<?php

/**
 * @file
 * Contains Views preprocess functions.
 */

use Drupal\eic_overviews\GlobalOverviewPages;

/**
 * Implements template_preprocess_views_view_field().
 */
function eic_community_preprocess_views_view_field(&$variables) {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];

  $key = $view->id() . '-' . $view->current_display . '-' . $variables['field']->field;

  switch ($key) {
    case 'taxonomy-subtopics-name':
      $variables['term_item'] = _eic_community_preprocess_ecl_tag($variables['row']->_entity)['tag'];
      break;

  }
}

/**
 * Implements template_preprocess_views_view_field().
 */
function eic_community_preprocess_views_view(&$variables) {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];
  $variables['is_compact'] = FALSE;

  switch ($view->id()) {
    case 'groups_homepage':
      // Adds call to actions.
      if ($view->current_display === 'block_groups_homepage') {
        if ($call_to_action_url = GlobalOverviewPages::getGlobalOverviewPageUrl('groups')) {
          $variables['call_to_action'] = [
            'link' => [
              'label' => 'View all groups',
              'path' => $call_to_action_url,
            ],
          ];
        }
      }
      elseif ($view->current_display === 'block_events_homepage') {
        if ($call_to_action_url = GlobalOverviewPages::getGlobalOverviewPageUrl('events')) {
          $variables['call_to_action'] = [
            'link' => [
              'label' => 'View all events',
              'path' => $call_to_action_url,
            ],
          ];
        }
      }
      $variables['is_compact'] = TRUE;
      break;

  }
}
