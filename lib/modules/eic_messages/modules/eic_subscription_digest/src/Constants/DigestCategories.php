<?php

namespace Drupal\eic_subscription_digest\Constants;

/**
 * Class DigestCategories
 *
 * @package Drupal\eic_subscription_digest\Constants
 */
final class DigestCategories {

  const NEWS_STORIES = 'news_stories';

  const GROUP = 'group';

  const EVENT = 'event';

  const ORGANISATION = 'organisation';

  /**
   * @return string[]
   */
  public static function getAll(): array {
    return [
      self::NEWS_STORIES,
      self::GROUP,
      self::EVENT,
      self::ORGANISATION,
    ];
  }

}
