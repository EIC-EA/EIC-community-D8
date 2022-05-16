<?php

namespace Drupal\eic_subscription_digest\Constants;

use DateInterval;

/**
 * Class DigestTypes
 *
 * @package Drupal\eic_message_subscriptions\Constants
 */
final class DigestTypes {

  const DAILY = 'daily';

  const WEEKLY = 'weekly';

  const MONTHLY = 'monthly';

  /**
   * @return string[]
   */
  public static function getAll(): array {
    return [
      self::DAILY,
      self::WEEKLY,
      self::MONTHLY,
    ];
  }

  /**
   * @param string $type
   *
   * @return \DateInterval
   * @throws \Exception
   */
  public static function getInterval(string $type): DateInterval {
    if (!in_array($type, self::getAll())) {
      throw new \InvalidArgumentException();
    }

    switch ($type) {
      case self::DAILY:
        $interval = 'P1D';
        break;
      case self::WEEKLY:
        $interval = 'P1W';
        break;
      case self::MONTHLY:
        $interval = 'P1M';
        break;
    }

    return new DateInterval($interval);
  }

}
