<?php

/**
 * @file
 * Contains Projects (node and group) related functions.
 *
 * @param array $variables
 *
 */

/**
 * Implements hook_preprocess_HOOK() for group--project--full.html.twig.
 */
function _eic_community_render_project_detail_page(&$variables, $group) {
  $view_id = 'project_content';

  if ($group->hasField('features') && !$group->get('features')->isEmpty()) {
    $features = array_column($group->get('features')->getValue(), 'value');
    $variables['horizon_feature_enabled'] = is_int(array_search('eic_groups_anchor_horizon_platform_result', $features));
    $variables['innovation_feature_enabled'] = is_int(array_search('eic_groups_anchor_innovations', $features));
  }

  // Render and set project coordinators and paricipants view.
  $variables['project_coordinators_view'] = _eic_community_get_rendered_view($view_id, 'project_coordinators')['#rows'] ?? _eic_community_get_rendered_view($view_id, 'project_coordinators');
  $variables['project_participants_view'] = _eic_community_get_rendered_view($view_id, 'project_participants')['#rows'] ?? _eic_community_get_rendered_view($view_id, 'project_participants');
}

/**
 * Helper function to load and render a view.
 *
 * @param string $view_id
 *   The ID of the view.
 * @param string $display_id
 *   The machine name of the display.
 *
 * @return array
 *   Rendered view output.
 */
function _eic_community_render_view($view_id, $display_id) {
  $view = \Drupal\views\Views::getView($view_id);
  if (is_object($view)) {
    $view->setDisplay($display_id);
    $view->preExecute();
    $view->execute();
    return $view->render();
  }
  return [];
}
