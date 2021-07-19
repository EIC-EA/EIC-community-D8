<?php

/**
 * Implements hook_preprocess_eic_content_actions().
 */
function eic_community_preprocess_eic_content_actions(array &$variables) {
  //TODO change content actions on group header block and GO message boxes and use this.
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

  $variables['actions'] = $actions;
}
