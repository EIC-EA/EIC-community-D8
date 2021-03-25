<?php

/**
 * @file
 * Contains implementation for hook_preprocess_responsive_image_formatter().
 */

/**
 * Implements hook_preprocess_responsive_image_formatter().
 *
 * For all responsive images, we need to set the ECL image classes.
 */
function eic_community_preprocess_responsive_image_formatter(&$variables) {
  $variables['responsive_image']['#attributes']['class'][] = 'ecl-u-width-100';
  $variables['responsive_image']['#attributes']['class'][] = 'ecl-u-height-auto';
}
