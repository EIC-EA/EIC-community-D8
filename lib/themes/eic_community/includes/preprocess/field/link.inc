<?php

/**
 * @file
 * Prepares variables for field link template.
 */

/**
 * Implements hook_preprocess_field() for link fields.
 */
function eic_community_preprocess_field__link(array &$variables) {
  foreach ($variables['items'] as &$value) {
    if (isset($value['content']['#options']['attributes']['class'])) {
      array_unshift($value['content']['#options']['attributes']['class'], 'ecl-link', 'ecl-link--default');
    }
    else {
      $value['content']['#options']['attributes']['class'] = [
        'ecl-link',
        'ecl-link--default',
      ];
    }
  }
}
