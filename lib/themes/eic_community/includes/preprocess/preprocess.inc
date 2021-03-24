<?php

/**
 * @file
 * Contains hook_preprocess() implementation.
 */

/**
 * Implements hook_preprocess().
 */
function eic_community_preprocess(&$variables) {
  $variables['eic_images_path'] = base_path() . drupal_get_path('theme', 'eic_community') . '/dist/images';
  $variables['eic_icon_path'] = $variables['eic_images_path'] . '/sprite/custom/sprites/custom.svg';
  $variables['eic_logo_path'] = $variables['eic_images_path'] . '/logo';
  $variables['eic_social_icon_path'] = $variables['eic_images_path'] . '/social-icons/sprites/icons-social.svg';

  $variables['ecl_component_library'] = theme_get_setting('component_library');
  $variables['ecl_branding'] = theme_get_setting('branding') ?? 'core';
  $variables['ecl_images_path'] = base_path() . drupal_get_path('theme',
      'eic_community') . '/dist/' . $variables['ecl_component_library'] . '/images';
  $variables['ecl_icon_path'] = $variables['ecl_images_path'] . '/icons/sprites/icons.svg';
  $variables['ecl_logo_path'] = $variables['ecl_images_path'] . '/logo';
  $variables['ecl_social_icon_path'] = $variables['ecl_images_path'] . '/social-icons/sprites/icons-social.svg';
  $variables['current_language_id'] = \Drupal::languageManager()
    ->getCurrentLanguage()
    ->getId();
}
