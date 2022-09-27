<?php

namespace Drupal\eic_user_login\Constants;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Contains constants representing the SMED user statuses.
 *
 * @package Drupal\eic_user_login\Constants
 */
final class SmedUserStatuses {

  use StringTranslationTrait;

  /**
   * User validated.
   *
   * @var string
   */
  const USER_VALID = 'user_valid';

  /**
   * User invited.
   *
   * @var string
   */
  const USER_INVITED = 'user_invited';

  /**
   * User approved incomplete.
   *
   * @var string
   */
  const USER_APPROVED_INCOMPLETE = 'user_approved_incomplete';

  /**
   * User pending.
   *
   * @var string
   */
  const USER_PENDING = 'user_pending';

  /**
   * User draft.
   *
   * @var string
   */
  const USER_DRAFT = 'user_draft';

  /**
   * User approved complete.
   *
   * @var string
   */
  const USER_APPROVED_COMPLETE = 'user_approved_complete';

  /**
   * User not bootstrapped.
   *
   * @var string
   */
  const USER_NOT_BOOTSTRAPPED = 'user_not_bootstrapped';

  /**
   * User unknown.
   *
   * @var string
   */
  const USER_UNKNOWN = 'user_unknown';

  /**
   * User blocked.
   *
   * @var string
   */
  const USER_BLOCKED = 'user_blocked';

  /**
   * User unsubscribed.
   *
   * @var string
   */
  const USER_UNSUBSCRIBED = 'user_unsubscribed';

  /**
   * Returns a list of possible statuses with their labels.
   *
   * @return array
   *   And array composed of key => label.
   */
  public static function getUserStatuses() {
    return [
      self::USER_VALID => t('User valid', [], ['context' => 'eic_user_login']),
      self::USER_APPROVED_COMPLETE => t('User approved complete', [], ['context' => 'eic_user_login']),
      self::USER_APPROVED_INCOMPLETE => t('User approved incomplete', [], ['context' => 'eic_user_login']),
      self::USER_DRAFT => t('User draft', [], ['context' => 'eic_user_login']),
      self::USER_PENDING => t('User pending', [], ['context' => 'eic_user_login']),
      self::USER_INVITED => t('User invited', [], ['context' => 'eic_user_login']),
      self::USER_NOT_BOOTSTRAPPED => t('User not boostrapped', [], ['context' => 'eic_user_login']),
      self::USER_BLOCKED => t('User blocked', [], ['context' => 'eic_user_login']),
      self::USER_UNSUBSCRIBED => t('User unsubscribed', [], ['context' => 'eic_user_login']),
      self::USER_UNKNOWN => t('User unknown', [], ['context' => 'eic_user_login']),
    ];
  }

}
