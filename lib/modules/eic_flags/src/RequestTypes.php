<?php

namespace Drupal\eic_flags;

/**
 * Provides flag request types.
 *
 * @package Drupal\eic_flags
 */
final class RequestTypes {

  const DELETE = 'delete';

  const ARCHIVE = 'archive';

  const BLOCK = 'block';

  const TRANSFER_OWNERSHIP = 'transfer_ownership';

  /**
   * Gets request timeout expiration options.
   *
   * @return array
   *   Array of expiration options (in days). 0 for no timeout.
   */
  public static function getRequestTimeoutExpirationOptions() {
    return [
      '0' => t('No timeout'),
      '2' => t('@days days', ['@days' => 2]),
      '5' => t('@days days', ['@days' => 5]),
      '10' => t('@days days', ['@days' => 10]),
      '30' => t('@days days', ['@days' => 30]),
    ];
  }

}
