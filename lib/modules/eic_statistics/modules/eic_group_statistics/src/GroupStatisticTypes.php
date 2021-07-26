<?php

namespace Drupal\eic_group_statistics;

/**
 * Provides statistic types to use in group statistics.
 *
 * @package Drupal\eic_group_statistics
 */
final class GroupStatisticTypes {

  /**
   * Database column name for group members counter.
   */
  const STAT_TYPE_MEMBERS = 'members';

  /**
   * Database column name for group comments counter.
   */
  const STAT_TYPE_COMMENTS = 'comments';

  /**
   * Database column name for group files counter.
   */
  const STAT_TYPE_FILES = 'files';

  /**
   * Database column name for group events counter.
   */
  const STAT_TYPE_EVENTS = 'events';

  /**
   * Returns an options array with all custom types.
   *
   * @return array
   *   An array containing all the types with machine name as key and label as
   *   value.
   */
  public static function getOptionsArray() {
    return [
      self::STREAM => t('Activity stream'),
      self::NOTIFICATION => t('Notification'),
      self::SUBSCRIPTION => t('Subscription'),
      self::LOG => t('Log'),
    ];
  }

}
