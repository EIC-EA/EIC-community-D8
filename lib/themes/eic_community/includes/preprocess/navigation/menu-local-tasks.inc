<?php

/**
 * @file
 * Contains implementation for hook_preprocess_menu_local_tasks().
 */

/**
 * Implements hook_preprocess_menu_local_tasks().
 */
function eic_community_preprocess_menu_local_tasks(array &$variables): void {
  // Sort local tasks by weight.
  uasort($variables['primary'],
    ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
  uasort($variables['secondary'],
    ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

  foreach ($variables['primary'] as $link) {
    /** @var Drupal\Core\Url $url */
    $url = $link['#link']['url'];
    if ($url->access($variables['user'])) {
      $variables['primary_links'][] = [
        'link' => [
          'path' => $url,
          'label' => $link['#link']['title'],
          'type' => 'standalone',
        ],
        'is_active' => $link['#active'],
      ];
    }
  }

  foreach ($variables['secondary'] as $link) {
    /** @var Drupal\Core\Url $url */
    $url = $link['#link']['url'];
    if ($url->access($variables['user'])) {
      $variables['secondary_links'][] = [
        'link' => [
          'path' => $url,
          'label' => $link['#link']['title'],
          'type' => 'standalone',
        ],
        'is_active' => $link['#active'],
      ];
    }
  }
}
