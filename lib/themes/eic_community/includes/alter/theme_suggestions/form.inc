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
