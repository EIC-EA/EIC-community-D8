<?php

/**
 * @file
 * Contains implementation for hook_preprocess_fieldset().
 */

/**
 * Implements hook_preprocess_fieldset().
 */
function eic_community_preprocess_fieldset(array &$variables): void {
  if (isset($variables['element']['#type']) && $variables['element']['#type'] == 'radios') {
    $variables['legend_display'] = FALSE;
  }
}
