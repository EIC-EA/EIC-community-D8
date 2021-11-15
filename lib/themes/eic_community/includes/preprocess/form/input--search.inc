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

/**
 * Helper function to additionally preprocess textfield search elements.
 *
 * Moves the label element to the input template and creates some helper
 * variables to be used with the ECL templates.
 *
 * @param array $element
 *   Set of element data.
 *
 * @return array
 *   The ECL array for the input element.
 */
function _eic_community_preprocess_search_input_text(array $element): array {
  // Process element attributes into an ECL input array.
  $ecl_array = [
    'id' => $element['#attributes']['id'],
    'name' => $element['#attributes']['name'] ?? '',
    'disabled' => isset($element['#attributes']['disabled']) ? $element['#attributes']['disabled'] : FALSE,
    'type' => $element['#attributes']['type'],
    'class' => $element['#attributes']['class'],
  ];

  // Map rest of the array elements.
  $ecl_array['invalid'] = !empty($element['#errors']);
  return $ecl_array;
}
