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
  $variables['eic_images_path'] = base_path() . \Drupal::service('extension.path.resolver')
    ->getPath('theme', 'eic_community') . '/dist/images';
  $variables['eic_icon_path'] = $variables['eic_images_path'] . '/sprite/custom/sprites/custom.svg';
  $variables['eic_logo_path'] = base_path() . \Drupal::service('extension.path.resolver')
    ->getPath('theme', 'eic_community') . '/images/logo';
  $variables['eic_mail_images_path'] = base_path() . \Drupal::service('extension.path.resolver')
    ->getPath('theme', 'eic_community') . '/mails/mail-dist/images';
  $variables['eic_social_icon_path'] = $variables['eic_images_path'] . '/social-icons/sprites/icons-social.svg';
  $variables['current_language_id'] = \Drupal::languageManager()
    ->getCurrentLanguage()
    ->getId();
  $variables['base_path'] = \Drupal::request()->getSchemeAndHttpHost();
}

/**
 * Transform a timestamp as time ago.
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

/**
 * Transform a timestamp as formatted date.
 *
 * @param int $timestamp
 *   The timestamp.
 *
 * @return string
 *   The date.
 */
function eic_community_get_teaser_formatted_date_display(int $timestamp) {
  return strtoupper(date('d F Y'));
}
