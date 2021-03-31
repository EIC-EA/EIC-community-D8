<?php

/**
 * @file
 * Contains implementation for hook_preprocess_html().
 */

/**
 * Implements hook_preprocess_html() for html.html.twig.
 *
 * Add css class for splash page.
 */
function eic_community_preprocess_html(array &$variables): void {
  if ('language_selection_page' === \Drupal::request()->attributes->get('_route')) {
    $variables['attributes']['class'][] = 'page-splash';
  }
}
