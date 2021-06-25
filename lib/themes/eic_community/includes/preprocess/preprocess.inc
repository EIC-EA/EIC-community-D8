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
  $variables['eic_images_path'] = base_path() . drupal_get_path('theme', 'eic_community') . '/dist/images';
  $variables['eic_icon_path'] = $variables['eic_images_path'] . '/sprite/custom/sprites/custom.svg';
  $variables['eic_logo_path'] = $variables['eic_images_path'] . '/logo';
  $variables['eic_social_icon_path'] = $variables['eic_images_path'] . '/social-icons/sprites/icons-social.svg';
  $variables['current_language_id'] = \Drupal::languageManager()
    ->getCurrentLanguage()
    ->getId();
}

/**
 * Transform a timestamp for a storybook template.
 */
function eic_community_get_teaser_time_display($timestamp) {
  $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($timestamp, ['granularity' => 1]);

  return t('@time ago', ['@time' => $time_ago]);
}

/**
 * Transform flags to storybook links.
 */
function eic_community_get_flag_links_display(array $flags = []) {
  $out = [];

  foreach ($flags as $key => $flag_metadata) {
    $flag_element = \Drupal::service('flag.link_builder')->build($flag_metadata[0], $flag_metadata[1], $flag_metadata[2]);
    if (isset($flag_element['#flag'])) {
      $counts = \Drupal::service('flag.count')->getEntityFlagCounts($flag_element['#flaggable']);
      $flag_count = empty($counts) || !isset($counts[$flag_element['#flag']->id()]) ? '0' : $counts[$flag_element['#flag']->id()];

      $icon_name = '';

      switch ($key) {
        case 'flag_follow_group':
          $icon_name = 'follow';
          break;

        case 'flag_recommend_group':
          $icon_name = 'like';
          break;

      }

      $out[] = [
        'extra_classes' => 'flag-' . $flag_element['#flag']->id() . ' js-flag-' . $flag_element['#flag']->id() . '-' . $flag_element['#flaggable']->id(),
        'link' => [
          'label' => $flag_element['#title']['#markup'] . " ($flag_count)",
          'path' => $flag_element['#attributes']['href'],
        ],
        'icon' => [
          'type' => 'custom',
          'name' => $icon_name,
        ],
      ];
    }
  }

  return $out;
}
