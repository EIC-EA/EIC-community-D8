<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_form_alter().
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function eic_community_theme_suggestions_form_alter(array &$suggestions, array $variables): void {
  $suggestions[] = $variables['theme_hook_original'] . '__' . $variables['element']['#form_id'];
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function eic_community_theme_suggestions_container_alter(array &$suggestions, array $variables): void {
  // Hide/show conditional fields based on field access.
  $hide_conditional_fields = [
    'publish_on',
    'publish_state',
    'unpublish_state',
    'publish_at',
  ];
  foreach ($hide_conditional_fields as $field_name) {
    if (
      isset($variables['element']['widget']['#field_name']) &&
      $variables['element']['widget']['#field_name'] === $field_name &&
      isset($variables['element']['widget'][0]['#access']) &&
      $variables['element']['widget'][0]['#access'] === FALSE
    ) {
      $suggestions[] = $variables['theme_hook_original'] . '__empty';
      break;
    }
  }
}
