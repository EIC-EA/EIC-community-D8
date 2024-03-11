<?php

namespace Drupal\eic_admin\Constants;

/**
 * Defines constants for the custom datepicker integration.
 *
 * @package Drupal\eic_admin\Constants
 */
final class Datepicker {

  const FIELDS_OVERRIDE_DATEPICKER = [
    'published_at[0][value][date]',
    'unpublish_on[0][value][date]',
    'publish_on[0][value][date]',
    'created[0][value][date]',
  ];

}
