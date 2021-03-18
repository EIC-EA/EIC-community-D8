<?php

/**
 * @file
 * Prepares variables for fragment banner template.
 */

use Drupal\Core\Render\Element;

/**
 * Implements hook_preprocess_fragment() for banner fragment.
 */
function eic_community_preprocess_fragment__banner(array &$variables) {
  // Adds h2 wrapper around title.
  $variables['content']['title'][0]['#prefix'] = '<h2>';
  $variables['content']['title'][0]['#suffix'] = '</h2>';

  // Massage field_cta_link in order to add extra ECL link classes.
  foreach (Element::children($variables['content']['field_cta_link']) as $index) {
    $variables['content']['field_cta_link'][$index]['#options']['attributes']['class'] = ['ecl-link--cta'];
  }
}
