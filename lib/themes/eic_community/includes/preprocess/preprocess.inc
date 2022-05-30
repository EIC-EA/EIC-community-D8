<?php

/**
 * @file
 * Contains hook_preprocess() implementation.
 */

/**
 * Implements hook_preprocess().
 */
function eic_community_preprocess(&$variables) {
  $variables['ecl_branding'] = 'core';
  $theme_path = base_path() . \Drupal::service('extension.path.resolver')
      ->getPath('theme', 'eic_community');
  $variables['eic_images_path'] = base_path() . $theme_path . '/dist/images';
  $variables['eic_icon_path'] = $variables['eic_images_path'] . '/sprite/custom/sprites/custom.svg';
  $variables['eic_logo_path'] = $variables['eic_images_path'] . '/logo';
  $variables['eic_mail_images_path'] = $theme_path . '/mails/mail-dist/images';
  $variables['eic_social_icon_path'] = $variables['eic_images_path'] . '/social-icons/sprites/icons-social.svg';
  $variables['current_language_id'] = \Drupal::languageManager()
    ->getCurrentLanguage()
    ->getId();
  $variables['base_path'] = \Drupal::request()->getSchemeAndHttpHost();
}

/**
 * Transform a timestamp for a storybook template.
 *
 * @param int $timestamp
 *   The timestamp.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup
 *   The translated string.
 */
function eic_community_get_teaser_time_display(int $timestamp) {
  $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($timestamp, ['granularity' => 1]);

  return t('@time ago', ['@time' => $time_ago]);
}
