<?php

/**
 * @file
 * Contains implementation for hook_preprocess_menu__main().
 */

/**
 * Implements hook_preprocess_menu__main().
 */
function eic_community_preprocess_menu__main(array &$variables): void {
  // Massage data to be compliant with ECL navigation menu data structure.
  $variables['links'] = array_map(function ($item) {
    return [
      'label' => $item['title'],
      'href' => $item['url'],
      'is_current' => $item['in_active_trail'],
    ];
  }, $variables['items']);

  foreach ($variables['items'] as $name => $link) {
    $variables['links'][$name]['children'] = array_map(function ($item) {
      return [
        'label' => $item['title'],
        'href' => $item['url'],
        'is_current' => $item['in_active_trail'],
      ];
    }, $variables['items'][$name]['below']);
  }
}
