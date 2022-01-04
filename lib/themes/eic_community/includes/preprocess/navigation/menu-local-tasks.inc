<?php

/**
 * @file
 * Contains implementation for hook_preprocess_menu_local_tasks().
 */

use Drupal\eic_flags\RequestTypes;

/**
 * Implements hook_preprocess_menu_local_tasks().
 */
function eic_community_preprocess_menu_local_tasks(array &$variables): void {
  // Sort local tasks by weight.
  uasort(
    $variables['primary'],
    ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']
  );
  uasort(
    $variables['secondary'],
    ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']
  );

  $current_node = \Drupal::routeMatch()->getParameter('node');

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

  if ($current_node instanceof \Drupal\node\NodeInterface) {
    $request_uri = \Drupal::request()->getRequestUri();
    $current_user = \Drupal::currentUser();

    $requests_url = [
      [
        'url' => $current_node->toUrl('new-request')->setRouteParameter('destination', $request_uri)->setRouteParameter('request_type', RequestTypes::ARCHIVE),
        'label' => t('Request archival'),
      ],
      [
        'url' => $current_node->toUrl('new-request')->setRouteParameter('destination', $request_uri)->setRouteParameter('request_type', RequestTypes::DELETE),
        'label' => t('Request deletion'),
      ],
    ];

    foreach ($requests_url as $request_url) {
      if (!$request_url['url']->access($current_user)) {
        continue;
      }

      $variables['primary_links'][] = [
        'link' => [
          'path' => $request_url['url'],
          'label' => $request_url['label'],
          'type' => 'standalone',
        ],
        'is_active' => 0,
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
