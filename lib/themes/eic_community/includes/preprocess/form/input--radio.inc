<?php

/**
 * @file
 * Contains implementation of hook_preprocess_input__radio().
 */

/**
 * Implements hook_preprocess_input__radio().
 */
function eic_community_preprocess_input__radio(array &$variables): void {
  _eic_community_preprocess_input_label_wrapper($variables);
}
