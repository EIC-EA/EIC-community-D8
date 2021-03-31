<?php

/**
 * @file
 * Contains implementation for hook_preprocess_radios().
 */

/**
 * Implements hook_preprocess_radios().
 */
function eic_community_preprocess_radios(array &$variables): void {
  $element = &$variables['element'];
  $variables['invalid'] = !empty($element['#errors']);

  if (isset($element['#title']) && $element['#title'] !== '') {
    $variables['label'] = ['#markup' => $element['#title']];
  }
}
