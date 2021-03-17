<?php

/**
 * @file
 * Prepares variables for paragraph tiles content template.
 */

use Drupal\Core\Render\Element;

/**
 * Implements hook_preprocess_paragraph() for tiles content paragraph.
 */
function eic_community_preprocess_paragraph__tiles_content(array &$variables) {
  // Adds card items content.
  foreach (Element::children($variables['content']['field_tiles']) as $index) {
    $variables['paragraph_content']['items'][$index]['content'] = &$variables['content']['field_tiles'][$index];
  }
}
