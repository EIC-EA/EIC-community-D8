<?php

/**
 * @file
 * Contains implementation for hook_preprocess_form_element().
 */

/**
 * Implements hook_preprocess_form_element().
 *
 * Disables displaying of the label for checkbox and radio elements, as the
 * label is already rendered in the input template.
 */
function eic_community_preprocess_form_element(array &$variables): void {
  if (in_array($variables['element']['#type'], ['checkbox', 'radio'])) {
    $variables['label_display'] = 'none';
  }

  if (in_array($variables['element']['#type'], ['select'])) {
    // Remove "container-inline" class from select lists.
    if (
      (isset($variables['attributes']['class'])) &&
      ($key = array_search('container-inline', $variables['attributes']['class'])) !== FALSE
    ) {
      unset($variables['attributes']['class'][$key]);
    }
  }

  $ecl_type_mappings = [
    'textfield' => 'text-input',
    'password' => 'text-input',
    'textarea' => 'text-area',
    'select' => 'select',
  ];
  if (isset($ecl_type_mappings[$variables['element']['#type']])) {
    $variables['ecl_type'] = $ecl_type_mappings[$variables['element']['#type']];
  }
}
