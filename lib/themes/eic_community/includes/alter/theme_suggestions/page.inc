<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_page_alter().
 */

/**
 * Implements hook_theme_suggestions_page_alter().
 */
function eic_community_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  // Adds suggestions for the front page.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    if (\Drupal::currentUser()->isAnonymous()) {
      $suggestions[] = 'page__front__anonymous';
    }
  }
}
