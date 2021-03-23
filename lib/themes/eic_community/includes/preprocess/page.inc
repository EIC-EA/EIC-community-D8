<?php

/**
 * @file
 * Prepares variables for the page template.
 */

/**
 * Implements hook_preprocess().
 */
function eic_community_preprocess(&$variables) {
  $variables['eic_images_path'] = base_path() . drupal_get_path('theme', 'eic_community') . '/dist/images';
  $variables['eic_icon_path'] = $variables['eic_images_path'] . '/sprite/custom/sprites/custom.svg';
  $variables['eic_logo_path'] = $variables['eic_images_path'] . '/logo';
  $variables['eic_social_icon_path'] = $variables['eic_images_path'] . '/social-icons/sprites/icons-social.svg';
}

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
