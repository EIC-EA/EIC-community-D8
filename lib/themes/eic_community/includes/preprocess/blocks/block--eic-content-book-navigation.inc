<?php

/**
 * @file
 * Contains preprocessor for block__eic_content_book_navigation.
 */

/**
 * Implements hook_preprocess_block__eic_content_book_navigation().
 */
function eic_community_preprocess_block__eic_content_book_navigation(&$variables) {
  $title = '';
  $node = \Drupal::routeMatch()->getParameter('node');

  if ($node instanceof Drupal\node\NodeInterface) {
    $parent = Drupal\node\Entity\Node::load($node->book['bid']);
    $title = $parent->getTitle();
  }

  $variables['title'] = $title;
  // Create hierarchical list of links maximum 3 levels deep.
  $items = [];
  $links = array_filter($variables['content'], function ($value, $key) {
    return is_int($key);
  }, ARRAY_FILTER_USE_BOTH);
  $current_path = \Drupal::service('path.current')->getPath();
  $path_alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);

  foreach ($links as $link) {
    foreach ($link['#items'] as $link_item) {
      $sub_items = [];

      if (count($link_item['below']) > 0) {
        foreach ($link_item['below'] as $sub_item) {
          $sub_sub_items = [];

          foreach ($sub_item['below'] as $sub_sub_item) {
            $sub_sub_items[] = [
              'link' => [
                'label' => $sub_sub_item['title'],
                'path' => $sub_sub_item['url'],
              ],
            ];
          }

          $sub_items[] = [
            'link' => [
              'label' => $sub_item['title'],
              'path' => $sub_item['url'],
            ],
            'items' => $sub_sub_items,
          ];
        }
      }

      $item = [
        'link' => [
          'label' => $link_item['title'],
          'path' => $link_item['url'],
        ],
        'items' => $sub_items,
      ];

      if (is_int(strpos($path_alias, $link_item['url']->toString()))) {
        $item['is_active'] = TRUE;
      }

      $items[] = $item;
    }
  }

  $variables['items'] = $items;

}
