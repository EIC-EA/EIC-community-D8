<?php

namespace Drupal\eic_user_login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Messenger\MessengerTrait;
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

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The SMED user manager.
   *
   * @var Drupal\eic_user_login\Service\SmedUserManager
   */
  protected $smedUserManager;

  /**
   * Constructs a new CasEventSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param Drupal\eic_user_login\Service\SmedUserManager $smed_user_manager
   *   The config factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SmedUserManager $smed_user_manager) {
    $this->configFactory = $config_factory;
    $this->smedUserManager = $smed_user_manager;
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

    // Check from SMED if user can be created.
    $smed_connection = new SmedUserConnection();
    dpm($event->getPropertyValues());
    dpm($event->getCasPropertyBag());
    dpm($event->getDrupalUsername());

    // Prevent the creation of the user account.
    $event->cancelAutomaticRegistration();

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

    // We only check/sync user against SMED if account is not active.
    // This is to avoid too much load on the SMED.
    if (!$account->isActive() && $this->configFactory->get('eic_user_login.settings')->get('check_sync_user')) {
      // Update user status from SMED to see if user status will be updated.
      // @todo Implement code.

    }

    try {
      $this->smedUserManager->isUserLoginAuthorised($account);
      $this->messenger()->addStatus($this->smedUserManager->getLoginMessage($account));
    }
    catch (SmedUserLoginException $e) {
      $this->messenger->addError($e->getUserMessage());
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
