<?php

/**
 * @file
 * Prepares variables for eic content actions.
 */

/**
 * Implements hook_preprocess_eic_content_actions().
 */
function eic_community_preprocess_eic_content_actions(array &$variables) {
  // @todo change content actions on group header block and GO message boxes and use this.
  $actions = [];
  foreach ($variables['actions'] as $link) {
    if (isset($link['links'])) {

      $items = [];

      foreach ($link['links'] as $item) {
        $tmp = [
          'link' => [
            'label' => $item['title'],
            'path' => $item['url'],
          ],
        ];

        if (isset($item['action'])) {
          $tmp = array_merge($tmp, $item['action']);
        }

        $items[] = $tmp;
      }

      $actions[] = [
        'label' => $link['label'],
        'items' => $items,
      ];
    }
    else {
      $tmp = [
        'link' => [
          'label' => $link['title'],
          'path' => $link['url'],
        ],
      ];

      if (isset($link['action'])) {
        $tmp = array_merge($tmp, $link['action']);
      }

      $actions[] = $tmp;
    }
  }

  if ($variables['user_activity_feed_link']) {
    $variables['user_activity_feed_link']['icon'] = [
      'type' => 'custom',
      'name' => 'user',
      'path' => $variables['eic_icon_path'],
    ];
    array_unshift($actions, $variables['user_activity_feed_link']);
  }

  $variables['actions'] = $actions;
}
