<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for group menu.
 */

use Drupal\Core\Cache\Cache;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_block() for group menu.
 */
function eic_community_preprocess_block__group_menu(&$variables, &$cache_tags = []) {
  $items = [];

  foreach ($variables['content']['#items'] as $link) {
    $items[] = [
      // Temporary workaround due to an issue with group_content_menu module.
      // @see https://www.drupal.org/project/group_content_menu/issues/3209011
      'is_active' => \Drupal::request()->getPathInfo() === $link['url']->toString(),
      'link' => [
        'label' => $link['title'],
        'path' =>  $link['url']->toString(),
      ],
    ];
  }

  $variables['group_menu'] = ['items' => $items];
}
