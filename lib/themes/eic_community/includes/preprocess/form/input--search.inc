<?php

/**
 * @file
 * Contains implementation for hook_preprocess_input__search().
 */

/**
 * Implements hook_preprocess_input().
 */
function eic_community_preprocess_input__search(array &$variables): void {
  $variables['input_array'] = _eic_community_preprocess_search_input_text($variables['element']);
}
