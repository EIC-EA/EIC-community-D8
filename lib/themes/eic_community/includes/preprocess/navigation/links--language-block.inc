<?php

/**
 * @file
 * Contains implementation for hook_preprocess_links() for language_block.
 */

/**
 * Implements hook_preprocess_links().
 */
function eic_community_preprocess_links__language_block(array &$variables): void {
  $variables['languages'] = [];
  $variables['current_language'] = [];
  foreach ($variables['links'] as $language_code => $link) {
    /** @var \Drupal\Core\Url $url */
    $url = $link['link']['#url'];
    $href = $url
      ->setOptions($link['link']['#options'])
      ->setAbsolute(TRUE)
      ->toString();

    $language_internal = $language_code;
    if (EuropeanUnionLanguages::hasLanguage($language_code)) {
      $language_internal = EuropeanUnionLanguages::getInternalLanguageCode($language_code);
    }

    $language = [
      'path' => $href,
      'label' => $link['link']['#title'],
      // Pass original language code which can be useful for some other
      // preprocessors.
      'hreflang' => $language_code,
      'lang' => $language_internal,
    ];

    if ($language_code === $variables['current_language_id']) {
      $variables['current_language'] = [
        'path' => $href,
        'hreflang' => $language_code,
        'lang' => $language_internal,
        'label' => $link['link']['#title'],
      ];
      $language['active'] = TRUE;
    }

    $variables['languages'][] = $language;
  }
}
