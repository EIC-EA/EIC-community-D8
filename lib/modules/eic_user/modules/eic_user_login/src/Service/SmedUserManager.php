<?php

namespace Drupal\eic_user_login\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_user_login\Exception\SmedUserLoginException;
use Drupal\user\UserInterface;

/**
 * Manager class for user interaction with SMED.
 */
class SmedUserManager {

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
   * EIC User login settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * SmedUserConnection constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('eic_user_login.settings');
  }

  /**
   * Processes SMED data and update user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account object.
   * @param array $data
   *   Data to be processed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateUserInformation(UserInterface $account, array $data = []) {
    $account->field_user_status->value = $data['field_user_status'];
    $account->save();
  }

  /**
   * Checks if the user account can log in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if user can login, FALSE otherwise.
   *
   * @throws \Drupal\eic_user_login\Exception\SmedUserLoginException
   */
  public function isUserLoginAuthorised(UserInterface $account) {
    $allowed_statuses = [
      self::USER_VALID,
      self::USER_APPROVED_COMPLETE,
    ];
    // @todo Replace with real value.
    $account_status = self::USER_VALID;

    // Check the user status.
    if (in_array($account_status, $allowed_statuses)) {
      return TRUE;
    }
    else {
      $exception = new SmedUserLoginException();
      $exception->setUserStatus($account_status);
      $exception->setUserMessage($this->getLoginMessage($account));
      throw $exception;
    }
  }

  /**
   * Returns a message for the user based on their account status.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return string
   *   The message associated to the status code.
   */
  public function getLoginMessage(UserInterface $account): string {
    // @todo Get real value.
    $account_status = self::USER_VALID;

    switch ($account_status) {
      case self::USER_VALID:
      case self::USER_APPROVED_COMPLETE:
        $message = $this->t('Welcome @username', ['@username' => $account->getDisplayName()]);
        break;

      case self::USER_APPROVED_INCOMPLETE:
        $message = $this->t('Welcome @username, before you can continue please complete your profile at <a href=":smed_url">:smed_url</a>', [
          '@username' => $account->getDisplayName(),
          ':smed_url' => 'https://google.be',
        ]);
        break;

      case self::USER_PENDING:
        $message = $this->t('Welcome @username, your account is pending approval, once approved you will receive a notification e-mail', [
          '@username' => $account->getDisplayName(),
        ]);
        break;

      case self::USER_INVITED:
        $message = $this->t('Please complete your profile at <a href=":smed_url">:smed_url</a>', [
          ':smed_url' => 'https://google.be',
        ]);
        break;

      case self::USER_NOT_BOOTSTRAPPED:
      case self::USER_BLOCKED:
        $message = $this->t('Please contact us via the <a href=":contact_form_url">contact form</a>', [
          ':contact_form_url' => Url::fromRoute('contact.site_page'),
        ]);
        break;

      case self::USER_UNSUBSCRIBED:
      case self::USER_UNKNOWN:
        $message = $this->t('Please register at %link_to_the_smed <a href=":smed_url">:smed_url</a>', [
          ':smed_url' => 'https://google.be',
        ]);
        break;

      default:
        $message = "";
        break;
    }

    return $message;
  }

}
