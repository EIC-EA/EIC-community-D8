<?php

/**
 * @file
 * Contains implementation for hook_preprocess_page().
 */

use Drupal\Core\Template\Attribute;

/**
 * Implements hook_preprocess_page().
 */
function eic_community_preprocess_page(array &$variables): void {
  $variables['main_attributes'] = new Attribute([
    'class' => ['ecl-u-pb-xl'],
    'id' => ['main-content'],
  ]);
}
