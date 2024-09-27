<?php

namespace Drupal\eic_user_login\Service;

use Drupal\Component\Datetime\TimeInterface;
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
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * SmedUserManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TimeInterface $time) {
    $this->config = $config_factory;
    $this->time = $time;
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
    if (!empty($data['user_dashboard_id'])) {
      $account->field_smed_id->value = $data['user_dashboard_id'];
    }
    if (!empty($data['user_status'])) {
      $account->field_user_status->value = $data['user_status'];
    }
    $account->field_updated_profile_by_service->value = $this->time->getRequestTime();

    switch ($account->field_user_status->value) {
      case SmedUserStatuses::USER_VALID:
      case SmedUserStatuses::USER_APPROVED_COMPLETE:
        $account->activate();
        break;

      case SmedUserStatuses::USER_APPROVED_INCOMPLETE:
      case SmedUserStatuses::USER_DRAFT:
      case SmedUserStatuses::USER_PENDING:
      case SmedUserStatuses::USER_INVITED:
      case SmedUserStatuses::USER_NOT_BOOTSTRAPPED:
      case SmedUserStatuses::USER_BLOCKED:
      case SmedUserStatuses::USER_UNSUBSCRIBED:
      case SmedUserStatuses::USER_ARCHIVED:
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

    $smed_url = $this->config->get('eic_user_login.settings')->get('user_registration_url');

    switch ($account_status) {
      case SmedUserStatuses::USER_VALID:
      case SmedUserStatuses::USER_APPROVED_COMPLETE:
        $message = $this->t('Welcome @username', ['@username' => $account->getDisplayName()]);
        break;

      case SmedUserStatuses::USER_APPROVED_INCOMPLETE:
      case SmedUserStatuses::USER_DRAFT:
        $message = $this->t('Welcome @username, <p>Almost there! It look like your registration is in <b>draft</b>.</p> <p>Please go to your <span class="register_button"><a class="ecl-link ecl-link--cta" href=":smed_url" target="_blank">draft registration</a></span></p><p>If you have any questions <a href=":contact_form_url">contact us</a>.</p>', [
          '@username' => $account->getDisplayName(),
          ':smed_url' => $smed_url,
        ]);
        break;

      case SmedUserStatuses::USER_PENDING:
        $message = $this->t('Dear @username, <br><p>Currently your access is under review.</p><p> If you do not hear from us in the coming days, please <a href=":contact_form_url">contact us</a>.</p>', [
          '@username' => $account->getDisplayName(),
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;

      case SmedUserStatuses::USER_INVITED:
        $message = $this->t('<p>Welcome to the EIC community!</p> <br>To get you started please <span class="register_button"><a class="ecl-link ecl-link--cta" href=":smed_url" target="_blank">register here</a></span>. <p>If you have any questions <a href=":contact_form_url">contact us</a>.</p>', [
          ':smed_url' => $smed_url,
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;

      case SmedUserStatuses::USER_NOT_BOOTSTRAPPED:
      case SmedUserStatuses::USER_BLOCKED:
        $message = $this->t('It looks like something went wrong, Please contact us via the <a href=":contact_form_url">contact form</a>.', [
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;

      case SmedUserStatuses::USER_UNSUBSCRIBED:
        $message = $this->t('It looks like something went wrong, please contact us via the <a href=":contact_form_url">contact form</a>.', [
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;

      case SmedUserStatuses::USER_ARCHIVED:
        $message = $this->t('Your account has been archived and is no longer active. If you think this is a mistake, please contact us via the <a href=":contact_form_url">contact form</a>.', [
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
        ]);
        break;

      case SmedUserStatuses::USER_UNKNOWN:
        $message = $this->t('<p>It looks like you are not a member yet. Interested? </p> Please <span class="register_button"><a class="ecl-link ecl-link--cta" href=":smed_url" target="_blank">register</a></span> <p>If you have any questions <a href=":contact_form_url">contact us</a>.</p>', [
          ':smed_url' => $smed_url,
          ':contact_form_url' => Url::fromRoute('contact.site_page')->toString(),
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
