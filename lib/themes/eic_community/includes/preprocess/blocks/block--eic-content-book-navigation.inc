<?php

/**
 * @file
 * Contains preprocessor for block__eic_content_book_navigation.
 */

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_block__eic_content_book_navigation().
 */
function eic_community_preprocess_block__eic_content_book_navigation(&$variables) {
  $title = '';
  $node = \Drupal::routeMatch()->getParameter('node');

  if ($node instanceof NodeInterface) {
    $parent = Node::load($node->book['bid']);
    $title = $parent->getTitle();
  }

  $variables['title'] = $title;

  $links = array_filter($variables['content'], function ($value, $key) {
    return is_int($key);
  }, ARRAY_FILTER_USE_BOTH);

  // Get trail urls related to the current path.
  $trail_urls = \Drupal::service('menu_trail_by_path.path_helper')->getUrls();

  // Create hierarchical list of links maximum 3 levels deep.
  $items = [];
  foreach ($links as $link) {
    $items = $items + eic_wiki_book_navigation_format_item($link['#items'], $trail_urls);
  }

  $variables['items'] = $items;
}
