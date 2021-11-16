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
    case 'group_discussions':
    case 'group_documents':
    case 'group_events':
    case 'group_related_groups':
      $suggestions[] = "views_view__featured_content_collection";
      break;

    case 'groups_homepage':
      $suggestions[] = "views_view__featured_content_collection__compact";
      break;

    case 'global_overviews':
      if ($variables['view']->getDisplay()->display['id'] === 'search') {
        return;
      }

      $suggestions[] = "views_view__teaser_overview";
      break;

  }
}
