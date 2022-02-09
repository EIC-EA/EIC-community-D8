<?php

namespace Drupal\eic_user_login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\eic_user_login\Exception\SmedUserLoginException;
use Drupal\eic_user_login\Service\SmedUserManager;
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
      CasHelper::EVENT_PRE_LOGIN => ['userPreLogin'],
    ];
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

    // We don't check/sync user against SMED if account is active.
    // This is to avoid too much load on the SMED.
    try {
      $this->smedUserManager->isUserLoginAuthorised($account);
      $this->messenger()->addStatus($this->smedUserManager->getLoginMessage($account));
    }
    catch (Exception $e) {

    }

    // Update user status from SMED to see if user status will be updated.
    // @todo Implement code.
    // Check again if user is authorised.
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
