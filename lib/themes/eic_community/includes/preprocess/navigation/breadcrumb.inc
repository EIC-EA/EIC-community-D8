<?php

/**
 * @file
 * Contains hook_preprocess_breadcrumb() implementation.
 */

/**
 * Implements hook_preprocess_breadcrumb().
 */
function eic_community_preprocess_breadcrumb(array &$variables): void {
  $request = \Drupal::request();
  $route_match = \Drupal::routeMatch();
  $title = \Drupal::service('title_resolver')
    ->getTitle($request, $route_match->getRouteObject());

  unset($variables['links']);
  $variables['links'] = array_map(function ($item) {
    return [
      'path' => $item['url'],
      'label' => $item['text'],
    ];
  }, $variables['breadcrumb']);

  // Add the title to the segments only if it's not empty.
  if (!empty($title)) {
    $variables['links'][] = [
      'label' => $title,
    ];
  }

  $variables['navigation_text'] = '';
  $variables['ellipsis_label'] = '';
  $variables['icon_file_path'] = $variables['ecl_icon_path'];
}
