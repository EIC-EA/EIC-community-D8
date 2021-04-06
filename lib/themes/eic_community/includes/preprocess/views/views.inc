<?php

/**
 * @file
 * Prepares variables for views template.
 */

/**
 * Implements hook_theme_suggestions_views_view_alter().
 */
function eic_community_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  $suggestions[] = "views_view__{$variables['view']->id()}";
  $suggestions[] = "views_view__{$variables['view']->id()}__{$variables['view']->current_display}";

  switch ($variables['view']->id()) {
    case 'group_related_news_stories':
      $suggestions[] = "views_view__featured_content_collection";
      break;

  }
}
