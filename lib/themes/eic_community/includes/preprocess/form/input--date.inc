<?php

/**
 * @file
 * Contains implementation for template_preprocess_input__date().
 */

/**
 * Implements template_preprocess_input__date().
 */
function eic_community_preprocess_input__date(array &$variables): void {
  $php_date_format =
    !empty($variables['attributes']['data-drupal-date-format'] && is_array($variables['attributes']['data-drupal-date-format']))
      ? reset($variables['attributes']['data-drupal-date-format'])
      : 'Y-m-d';
  $format_map = [
    // @todo find a cleverer way of mapping PHP date formats to the expected
    // ECL ones.
    'Y-m-d' => 'YYYY-MM-DD',
  ];
  $ecl_format = $format_map[$php_date_format] ?? 'YYYY-MM-DD';
  $variables['#attached']['library'][] = 'eic_community/ecl_datepicker';
  $variables['#attached']['drupalSettings']['eic_community']['ecl_datepicker_format'] = $ecl_format;
  $variables['placeholder'] = $ecl_format;
}
