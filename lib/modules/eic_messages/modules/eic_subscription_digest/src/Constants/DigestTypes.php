<?php

namespace Drupal\eic_subscription_digest\Constants;

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

}
