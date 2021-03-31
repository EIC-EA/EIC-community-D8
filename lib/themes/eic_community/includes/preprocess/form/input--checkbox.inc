<?php

/**
 * @file
 * Contains implementation for eic_community_preprocess_input__checkbox().
 */

/**
 * Implements hook_preprocess_input__checkbox().
 */
function eic_community_preprocess_input__checkbox(array &$variables): void {
  _eic_community_preprocess_input_label_wrapper($variables);
}
