<?php

/**
 * @file
 * Contains hook_preprocess_breadcrumb() implementation.
 */

use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_preprocess_breadcrumb().
 */
function eic_community_preprocess_breadcrumb(array &$variables): void {
  $request = \Drupal::request();
  $route_match = \Drupal::routeMatch();

  switch ($route_match->getRouteName()) {
    case 'entity.node.canonical':
      $node = $route_match->getParameter('node');
      // If the current node is a book page that belongs to a group, we
      // replace the last breadcrumb slug title with "Wiki".
      if (GroupContent::loadByEntity($node)) {
        if ($node->bundle() == 'book') {
          $title = t('Wiki');
        }
      }
      break;

    case 'eic_groups.about_page':
      $title = t('About');
      break;

  }

  if (!isset($title)) {
    $title = \Drupal::service('title_resolver')
      ->getTitle($request, $route_match->getRouteObject());
  }

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
  $variables['icon_file_path'] = $variables['eic_icon_path'];
}
