<?php

/**
 * @file
 * Contains implementation of hook_preprocess_eic_group_moderated_message_box().
 */

/**
 * Implements hook_preprocess_eic_group_overview_message_block().
 */
function eic_community_preprocess_eic_group_moderated_message_box(array &$variables) {
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
        'icon' => [
          'name' => 'plus',
          'type' => 'ui',
        ],
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

  $variables['actions'] = $actions;
}
