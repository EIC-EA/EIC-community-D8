<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for eic_group_header.
 */

/**
 * Implements hook_preprocess_eic_action_block().
 */
function eic_community_preprocess_eic_action_block(array &$variables) {
  $links = [];
  /** @var \Drupal\Core\Link $link */
  foreach ($variables['links'] as $link) {
    $links[] = [
      'link' => [
        'label' => $link->getText(),
        'path' => $link->getUrl()->toString(),
      ],
    ];
  }
  $variables['extra_classes'] = '';
  $variables['links'] = $links;
}
