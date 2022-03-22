<?php

namespace Drupal\eic_user_login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_user_login\Exception\SmedUserLoginException;
use Drupal\eic_user_login\Service\SmedUserManager;
use Drupal\eic_user_login\Service\SmedUserConnection;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber class for cas related events.
 */
class CasEventSubscriber implements EventSubscriberInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The SMED user manager.
   *
   * @var \Drupal\eic_user_login\Service\SmedUserManager
   */
  protected $smedUserManager;

  /**
   * The SMED user connection.
   *
   * @var \Drupal\eic_user_login\Service\SmedUserConnection
   */
  protected $smedUserConnection;

  /**
   * Constructs a new CasEventSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\eic_user_login\Service\SmedUserManager $smed_user_manager
   *   The SMED user manager.
   * @param \Drupal\eic_user_login\Service\SmedUserConnection $smed_user_connection
   *   The SMED user connection.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SmedUserManager $smed_user_manager,
    SmedUserConnection $smed_user_connection
  ) {
    $this->configFactory = $config_factory;
    $this->smedUserManager = $smed_user_manager;
    $this->smedUserConnection = $smed_user_connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CasHelper::EVENT_PRE_REGISTER => ['userPreRegister'],
      CasHelper::EVENT_PRE_LOGIN => ['userPreLogin'],
    ];
  }

  /**
   * React to a user logging in with cas when user account does not yet exist.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   Cas pre register event.
   */
  public function userPreRegister(CasPreRegisterEvent $event) {
    // Check if user should checked/synced against SMED.
    if ($this->configFactory->get('eic_user_login.settings')->get('check_sync_user') !== TRUE) {
      return;
    }

    // Prevent the creation of the user account.
    $event->cancelAutomaticRegistration();

    $this->messenger()->addStatus($this->t('Please register at <a href=":smed_url">:smed_url</a>', [
      ':smed_url' => 'https://google.be',
    ]));

  }

  /**
   * React to a user trying to login with cas.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   Cas pre register event.
   */
  public function userPreLogin(CasPreLoginEvent $event) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $event->getAccount();

    // Check if we have a proper value for the SMED ID.
    if ($account->hasField('field_smed_id') && !$account->get('field_smed_id')->isEmpty()) {
      // We only check/sync user against SMED if account is not active.
      // This is to avoid too much load on the SMED.
      if (!$account->isActive() && $this->configFactory->get('eic_user_login.settings')->get('check_sync_user')) {

        // Fetch information from SMED.
        $data = [
          'user_dashboard_id' => $account->field_smed_id->value,
          'email' => $account->getEmail(),
          'username' => $account->getAccountName(),
//          'user_dashboard_id' => 1,
//          'email' => 'demo@com.com',
//          'username' => 'nsireste',
        ];

        if ($result = $this->smedUserConnection->queryEndpoint($data)) {
          // Update the user status.
          $this->smedUserManager->updateUserInformation($account, $result);
        }
      }
    }

    try {
      $this->smedUserManager->isUserLoginAuthorised($account);
      $this->messenger()->addStatus($this->smedUserManager->getLoginMessage($account));
    }
    catch (SmedUserLoginException $e) {
      $this->messenger()->addError($e->getUserMessage());
    }
  }

  /**
   * Checks if user account is authorised to login.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account object.
   *
   * @return bool
   *   TRUE if user is allowed to login, FALSE otherwise.
   */
  protected function isUserAuthorised(UserInterface $account) {
    if ($account->isActive()) {
      return TRUE;
    }

    return FALSE;
  }

}
