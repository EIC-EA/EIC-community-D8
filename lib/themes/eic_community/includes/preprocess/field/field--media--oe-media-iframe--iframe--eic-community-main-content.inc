<?php

/**
 * @file
 * Contains implementation for iframe media preprocessor.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_field__media__oe_media_iframe__iframe__eic_community_main_content(array &$variables): void {
  // Set iframe aspect ratio when rendering "eic_community_main_content" display
  // mode.
  $variables['ratio'] = $variables['element']['#object']->get('oe_media_iframe_ratio')->value ?? 'custom';
}
