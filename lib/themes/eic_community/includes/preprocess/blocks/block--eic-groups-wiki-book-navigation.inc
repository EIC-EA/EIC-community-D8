<?php

/**
 * @file
 * Contains preprocessor for block__eic_groups_wiki_book_navigation.
 */

use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Implements hook_preprocess_block__eic_groups_wiki_book_navigation().
 */
function eic_community_preprocess_block__eic_groups_wiki_book_navigation(&$variables)
{
  $links = array_filter($variables['content'], function ($value, $key) {
    return is_int($key);
  }, ARRAY_FILTER_USE_BOTH);
  $current_path = \Drupal::service('path.current')->getPath();
  $path_alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);

  // Create hierarchical list of links maximum 3 levels deep.
  $items = [];
  foreach ($links as $link) {
    $items = $items + eic_wiki_book_navigation_format_item($link['#items'], $path_alias);
  }

  $variables['items'] = $items;
  // Add wiki contributors.
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    $author_id = $node->uid->getString();
    $author_account = User::load($author_id);
    $author = eic_community_get_teaser_user_display($author_account);

    $author['title'] = $author['name'];
    unset($author['name']);
    // @todo Add more info to the teaser.
    $variables['author'] = $author;
  }
}

/**
 * @param array $items
 * @param string $path_alias
 * @return array
 */
function eic_wiki_book_navigation_format_item($items, $path_alias)
{
  $formatted_items = [];
  foreach ($items as $link_item) {
    $item = [
      'link' => [
        'label' => $link_item['title'],
        'path' => $link_item['url'],
      ]
    ];

    if (is_int(strpos($path_alias, $link_item['url']->toString()))) {
      $item['is_active'] = TRUE;
    }

    if (!empty($link_item['below'])) {
      $item['items'] = eic_wiki_book_navigation_format_item($link_item['below'], $path_alias);
    }

    $formatted_items[] = $item;
  }

  return $formatted_items;
}
