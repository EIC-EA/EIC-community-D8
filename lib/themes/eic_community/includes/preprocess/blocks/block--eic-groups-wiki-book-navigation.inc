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
function eic_community_preprocess_block__eic_groups_wiki_book_navigation(&$variables) {
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
 * Formats wiki book navigation links to use in EIC templates.
 *
 * @param \Drupal\Core\Url[] $items
 *   Wiki book navigation urls.
 * @param \Drupal\Core\Url[] $trail_urls
 *   Array of trail urls related to the current path.
 *
 * @return array
 *   Formatted array of Wiki book navigation urls.
 */
function eic_wiki_book_navigation_format_item(array $items, array $trail_urls = []) {
  $formatted_items = [];
  foreach ($items as $link_item) {
    $item = [
      'link' => [
        'label' => $link_item['title'],
        'path' => $link_item['url'],
      ],
    ];

    foreach ($trail_urls as $key => $trail_url) {
      if ($trail_url->toString() === $link_item['url']->toString()) {
        $item['is_active'] = TRUE;

        // Last key of the active trail which means this is the last item in
        // the active trail.
        if ($key === array_key_last($trail_urls)) {
          $item['is_current'] = TRUE;
        }

        unset($trail_urls[$key]);
        break;
      }
    }

    if (!empty($link_item['below'])) {
      $item['items'] = eic_wiki_book_navigation_format_item($link_item['below'], $trail_urls);
    }

    $formatted_items[] = $item;
  }

  return $formatted_items;
}
