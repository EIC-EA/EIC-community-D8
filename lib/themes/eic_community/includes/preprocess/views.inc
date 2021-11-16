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

  $key = $view->id() . '-' . $view->current_display;
  switch ($key) {
    case 'groups_homepage-block_groups_homepage':
      if ($call_to_action_url = GlobalOverviewPages::getGlobalOverviewPageUrl('groups')) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all groups'),
            'path' => $call_to_action_url,
          ],
        ];
      }
      break;

    case 'groups_homepage-block_events_homepage':
      if ($call_to_action_url = GlobalOverviewPages::getGlobalOverviewPageUrl('events')) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all events'),
            'path' => $call_to_action_url,
          ],
        ];
      }
      break;

  }
}
