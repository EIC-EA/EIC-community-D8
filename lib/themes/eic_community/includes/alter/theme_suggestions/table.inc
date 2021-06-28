<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_table_alter().
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function eic_community_theme_suggestions_table_alter(array &$suggestions, array $variables): void {
  $currentTheme = \Drupal::service('theme.manager')->getActiveTheme();

  if ($currentTheme->getName() === 'eic_community' &&
    (isset($variables['attributes']['id']) && strpos($variables['attributes']['id'], 'field-') !== false)) {
    $suggestions[] = 'table__with_fields';
  }
}
