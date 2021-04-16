<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block__system_branding_block().
 */

/**
 * Implements hook_preprocess_block__system_branding_block().
 *
 * Add required variables to follow ECL guidelines.
 */
function eic_community_preprocess_block__system_branding_block(&$variables) {
  $variables['site_logo_alt'] = !empty($variables['site_name']) ?
    t('@site_name logo', ['@site_name' => $variables['site_name']]) :
    '';

  $route_options = ['absolute' => TRUE];
  $variables['site_logo_href'] = Url::fromRoute('<front>', [], $route_options)
    ->toString();
}
