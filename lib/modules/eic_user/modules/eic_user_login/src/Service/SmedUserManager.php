<?php

namespace Drupal\eic_user_login\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\eic_user_login\Constants\SmedUserStatuses;
use Drupal\eic_user_login\Exception\SmedUserLoginException;
use Drupal\user\UserInterface;

/**
 * Manager class for user interaction with SMED.
 */
class SmedUserManager {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * SmedUserManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
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
    $account->field_user_status->value = $data['user_status'];

    switch ($account->field_user_status->value) {
      case SmedUserStatuses::USER_VALID:
      case SmedUserStatuses::USER_APPROVED_COMPLETE:
        $account->activate();
        break;

      case SmedUserStatuses::USER_APPROVED_INCOMPLETE:
      case SmedUserStatuses::USER_PENDING:
      case SmedUserStatuses::USER_INVITED:
      case SmedUserStatuses::USER_NOT_BOOTSTRAPPED:
      case SmedUserStatuses::USER_BLOCKED:
      case SmedUserStatuses::USER_UNSUBSCRIBED:
      case SmedUserStatuses::USER_UNKNOWN:
        $account->block();
        break;

      default:
        // If status is not known, do nothing.
        break;
    }
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
      SmedUserStatuses::USER_VALID,
      SmedUserStatuses::USER_APPROVED_COMPLETE,
    ];
    $account_status = $account->field_user_status->value;

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
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The message associated to the status code.
   */
  public function getLoginMessage(UserInterface $account): TranslatableMarkup {
    $account_status = $account->field_user_status->value;

    $smed_url = $this->config->get('eic_webservices.settings')->get('smed_url');

    switch ($account_status) {
      case SmedUserStatuses::USER_VALID:
      case SmedUserStatuses::USER_APPROVED_COMPLETE:
        $message = $this->t('Welcome @username', ['@username' => $account->getDisplayName()]);
        break;

      case SmedUserStatuses::USER_APPROVED_INCOMPLETE:
        $message = $this->t('Welcome @username, before you can continue please complete your profile at <a href=":smed_url" target="_blank">:smed_url</a>.', [
          '@username' => $account->getDisplayName(),
          ':smed_url' => $smed_url,
        ]);
        break;

      case SmedUserStatuses::USER_PENDING:
        $message = $this->t('Welcome @username, your account is pending approval, once approved you will receive a notification e-mail.', [
          '@username' => $account->getDisplayName(),
        ]);
        break;

      case SmedUserStatuses::USER_INVITED:
        $message = $this->t('Please complete your profile at <a href=":smed_url" target="_blank">:smed_url</a>.', [
          ':smed_url' => $smed_url,
        ]);
        break;

      case SmedUserStatuses::USER_NOT_BOOTSTRAPPED:
      case SmedUserStatuses::USER_BLOCKED:
        $message = $this->t('Please contact us via the <a href=":contact_form_url">contact form</a>.', [
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;

      case SmedUserStatuses::USER_UNSUBSCRIBED:
      case SmedUserStatuses::USER_UNKNOWN:
        $message = $this->t('Please register at <a href=":smed_url" target="_blank">:smed_url</a>.', [
          ':smed_url' => $smed_url,
        ]);
        break;

      default:
        $message = $this->t('An error occurred. Please contact us via the <a href=":contact_form_url">contact form</a>.', [
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;
    }

    return $message;
  }

}
