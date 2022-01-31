<?php

/**
 * @file
 * Prepares variables for node wiki templates.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Implements hook_preprocess_node() for wiki node.
 */
function eic_community_preprocess_node__wiki_page(array &$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  switch ($variables['view_mode']) {
    case 'full':
      $actions = [];
      $content = [];
      foreach ($variables['elements'] as $key => $element) {
        // Get the links to add a new wiki page.
        if (strpos($key, 'link_add') !== FALSE && strpos($key, 'renderable') !== FALSE) {
          $actions[]['link'] = [
            'label' => $element['#title'],
            'path' => $element['#url'],
          ];
        }
        // Limit the wiki content output to the fields.
        if (strpos($key, 'field_') !== FALSE) {
          $content[] = $element;
        }
      }

      $variables['content'] = $content;
      $variables['actions']['items'] = $actions;
      break;

    case 'teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);
      $teaser['description'] = Unicode::truncate(strip_tags($node->get('field_body')->value), 300, TRUE, TRUE);
      $teaser['type'] = [
        'label' => $node->type->entity->label(),
        'icon' => [
          'name' => 'wiki',
          'type' => 'custom',
        ],
      ];

      // Remove unwanted items.
      $teaser['stats'] = [];

      $variables['story_item'] = $teaser;
      break;

  }

}
