<?php

/**
 * @file
 * Common logic for views exposed forms.
 */

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_HOOK().
 */
function _eic_community_preprocess_views_exposed_form(&$variables) {
  // Make sure we have unique ids when views exposed forms are shown
  // multiple times on the page.
  $unique_id = Html::getUniqueId($variables['element']['#id']);
  $variables['attributes']['id'] = $variables['attributes']['data-drupal-selector'] = $unique_id;
}
