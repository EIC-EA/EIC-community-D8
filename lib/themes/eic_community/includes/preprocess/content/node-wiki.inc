<?php

/**
 * @file
 * Prepares variables for node wiki templates.
 */

/**
 * Implements hook_preprocess_node() for wiki node.
 */
function eic_community_preprocess_node__wiki_page(array &$variables) {
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
}
