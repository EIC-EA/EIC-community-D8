<?php

namespace Drupal\eic_migrate\Constants;

/**
 * Defines miscellaneous constants.
 *
 * @package Drupal\eic_migrate\Constants
 */
final class Misc {

  /**
   * Text format mappings.
   *
   * @var string[]
   */
  protected const TEXT_FORMAT_MAPPINGS = [
    'full_html' => 'full_html',
    'filtered_html' => 'filtered_html',
    'plain_text' => 'plain_text',
    'mail' => 'basic_text',
  ];

  /**
   * Maps old text formats to new ones.
   *
   * @param string $text_format_id
   *   The old text format.
   *
   * @return false|mixed|string
   *   The new text format or FALSE if not found.
   */
  public static function getTextFormat(string $text_format_id) {
    return self::TEXT_FORMAT_MAPPINGS[$text_format_id] ?? FALSE;
  }

}
