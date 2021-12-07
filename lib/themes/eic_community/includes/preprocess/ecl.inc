<?php

/**
 * @file
 * Contains ECL components preprocess functions.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_helper\DateTimeHelper;
use Drupal\taxonomy\TermInterface;

/**
 * Preprocesses a timestamp into an array of values for ECL Date block template.
 *
 * @param int $timestamp
 *   The timestamp.
 * @param \DateTimeZone $timezone
 *   The timezone to use. Defaults to server timezone.
 * @param string $format
 *   The date format to display.
 *
 * @return array
 *   Array of key/value pairs suitable for the ECL Date block template.
 *
 * @see ecl-date-block.html.twig
 */
function _eic_community_preprocess_ecl_date_block(int $timestamp, DateTimeZone $timezone = NULL, $format = DateTimeHelper::DATE_FORMAT_LONG) {
  $date = new DateTime();
  if (empty($timezone)) {
    $timezone = $date->getTimezone();
  }
  $date->setTimezone($timezone);
  $date->setTimestamp($timestamp);

  switch ($format) {
    case DateTimeHelper::DATE_FORMAT_SHORT:
      $value = [
        'day' => $date->format('d'),
        'month' => $date->format('M'),
        'year' => $date->format('Y'),
        'date_time' => '',
        'month_full' => $date->format('F'),
        'extra_classes' => '',
        'extra_attributes' => [],
      ];
      break;

    default:
      $value = [
        'day' => $date->format('d'),
        'month' => $date->format('m'),
        'year' => $date->format('Y'),
        'date_time' => '',
        'month_full' => $date->format('F'),
        'extra_classes' => '',
        'extra_attributes' => [],
      ];
      break;

  }

  return $value;
}

/**
 * Preprocesses an array of taxonomy terms into an array for ECL tag template.
 *
 * @param \Drupal\taxonomy\TermInterface $taxonomy_term
 *   An array of taxonomy terms.
 * @param string $type
 *   The type of tag to provide. Possible values are:
 *   - link
 *   - removable
 *   - display
 *   - button (deprecated)
 *   Defaults to 'link'.
 *
 * @return array
 *   Array of key/value pairs suitable for ECL tag template.
 *
 * @see ecl-tag.html.twig
 */
function _eic_community_preprocess_ecl_tag(TermInterface $taxonomy_term, string $type = 'link') {
  return [
    'tag' => [
      'type' => $type,
      'label' => $taxonomy_term->get('name')->getString(),
      'path' => $taxonomy_term->toUrl()->toString(),
      'aria_label' => '',
    ],
    'default_icon_path' => '',
    'extra_classes' => '',
    'extra_attributes' => [],
  ];
}

/**
 * Returns the status of an entity based on a date range.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to check.
 * @param string $field_name
 *   The date_range field name.
 *
 * @return string
 *   The status of the event. Can be one of the following values:
 *   - upcoming
 *   - ongoing
 *   - past
 *   - undefined
 */
function _eic_community_get_ecl_date_block_variant(EntityInterface $entity, string $field_name) {
  $state = \Drupal::service('eic_helper.datetime')->getDateRangeStatus($entity, $field_name);

  switch ($state) {
    case 'upcoming':
    case 'undefined':
    default:
      return 'default';

    case 'ongoing':
      return 'ongoing';

    case 'past':
      return 'past';

  }
}
