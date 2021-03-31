<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_input_alter().
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 *
 * Adds a template suggestion for type time input fields.
 */
function eic_community_theme_suggestions_input_alter(array &$suggestions, array $variables): void {
  $element = $variables['element'];

  if ($element['#type'] === 'date' && $element['#attributes']['type'] === 'time') {
    $suggestions[] = 'input__time';
  }
}
