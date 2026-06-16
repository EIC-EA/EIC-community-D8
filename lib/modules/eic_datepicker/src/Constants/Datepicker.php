<?php

namespace Drupal\eic_datepicker\Constants;

/**
 * Defines constants for the custom datepicker integration.
 *
 * @package Drupal\eic_datepicker\Constants
 */
final class Datepicker {
  const FIELDS_OVERRIDE_DATEPICKER = [
    'published_at[0][value][date]',
    'unpublish_on[0][value][date]',
    'publish_on[0][value][date]',
    'created[0][value][date]',
    'field_date_range[0][value][date]',
    'field_date_range[0][end_value][date]',
    'field_event_registration_date[0][value][date]',
    'field_event_registration_date[0][end_value][date]',
  ];
}
