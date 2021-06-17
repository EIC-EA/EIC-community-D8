<?php

namespace Drupal\eic_messages;

/**
 * Provides custom types for Message templates.
 *
 * @package Drupal\eic_messages
 */
final class MessageTemplateTypes {

  /**
   * The Stream type.
   *
   * @var string
   */
  const STREAM = 'stream';

  /**
   * The Notification type.
   *
   * @var string
   */
  const NOTIFICATION = 'notification';

  /**
   * The Subscription type.
   *
   * @var string
   */
  const SUBSCRIPTION = 'subscription';

  /**
   * The Log type.
   *
   * @var string
   */
  const LOG = 'log';

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
