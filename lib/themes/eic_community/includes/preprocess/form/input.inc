<?php

/**
 * @file
 * Common logic for input preprocessing.
 */

/**
 * Helper function to additionally preprocess checkbox and radio elements.
 *
 * Moves the label element to the input template and creates some helper
 * variables to be used with the ECL templates.
 *
 * @param array $variables
 *   Set of available variables.
 */
function _eic_community_preprocess_input_label_wrapper(array &$variables): void {
  $element = &$variables['element'];
  $variables['invalid'] = !empty($element['#errors']);

  if (isset($element['#title']) && $element['#title'] !== '') {
    $variables['title'] = ['#markup' => $element['#title']];
  }

  // Generate the extra attributes array from the general attributes.
  $extra_attributes = !empty($variables['attributes']) ? $variables['attributes'] : [];
  $predefined_attributes = [
    'id' => 'id',
    'name' => 'name',
    'value' => 'value',
    'disabled' => 'disabled',
  ];
  $extra_attributes = array_diff_key($extra_attributes, $predefined_attributes);
  foreach ($extra_attributes as $key => $value) {
    if (is_string($value)) {
      $variables['input_attributes'][] = [
        'name' => $key,
        'value' => $value,
      ];
    }
  }
}
