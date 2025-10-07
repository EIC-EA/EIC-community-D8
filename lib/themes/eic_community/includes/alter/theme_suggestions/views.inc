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
    case 'organisation_content':
    case 'organisation_projects':
      $suggestions[] = 'views_view__organisation_featured_content_collection';
      break;

    case 'group_related_news_stories':
    case 'group_discussions':
    case 'group_documents':
    case 'group_events':
    case 'group_related_groups':
    case 'taxonomy_related_content':

      switch ($variables['view']->current_display) {
        case 'experts':
          $suggestions[] = "views_view__featured_contributors";
          break;

        default:
          $suggestions[] = "views_view__featured_content_collection";
          break;

      }
      break;

    case 'taxonomy':
      switch ($variables['view']->current_display) {
        case 'subtopics':
          $suggestions[] = "views_view__featured_topics";
          break;

      }
      break;

    case 'groups':
    case 'global_index_group_content':
      $suggestions[] = "views_view__teaser_overview_with_wrapper";
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

/**
 * Implements hook_theme_suggestions_views_view_unformatted_alter().
 */
function eic_community_theme_suggestions_views_view_unformatted_alter(array &$suggestions, array $variables) {
  // Add views specific suggestions.
  switch ($variables['view']->id()) {
    case 'taxonomy':
      $suggestions[] = 'views_view_unformatted__nude';
      break;
  }

  // Add suggestions for all views.
  $suggestions[] = "views_view_unformatted__{$variables['view']->id()}";
  $suggestions[] = "views_view_unformatted__{$variables['view']->id()}__{$variables['view']->current_display}";
}
