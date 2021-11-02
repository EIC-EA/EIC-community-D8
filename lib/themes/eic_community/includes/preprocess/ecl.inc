<?php

/**
 * @file
 * Contains ECL components preprocess functions.
 */

/**
 * Preprocesses a timestamp into an array of values for ECL Date block template.
 *
 * @param int $timestamp
 *   The timestamp.
 * @param \DateTimeZone $timezone
 *   The timezone to use. Defaults to server timezone.
 *
 * @return array
 *   Array of key/value pairs suitable for the ECL Date block template.
 *
 * @see ecl-date-block.html.twig
 */
function _eic_community_preprocess_ecl_date_block(int $timestamp, DateTimeZone $timezone = NULL) {
  $date = new DateTime();
  if (empty($timezone)) {
    $timezone = $date->getTimezone();
  }
  $date->setTimezone($timezone);
  $date->setTimestamp($timestamp);

  return [
    'variant' => '',
    'day' => $date->format('d'),
    'month' => $date->format('m'),
    'year' => $date->format('Y'),
    'date_time' => '',
    'month_full' => $date->format('F'),
    'extra_classes' => '',
    'extra_attributes' => [],
  ];
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
    'type' => $type,
    'label' => $taxonomy_term->get('name')->getString(),
    'path' => $taxonomy_term->toUrl()->toString(),
    'aria_label' => '',
  ];
}
