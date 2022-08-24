<?php

namespace Drupal\eic_user_login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Event\CasPreUserLoadEvent;
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
      CasHelper::EVENT_PRE_USER_LOAD => ['userPreLoad'],
    ];
  }

  /**
   * React to a user logging in with cas when user account does not yet exist.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   Cas pre register event.
   */
  public function userPreRegister(CasPreRegisterEvent $event) {
    // Check if user can register without SMED.
    if ($this->configFactory->get('eic_user_login.settings')->get('allow_user_register') === TRUE) {
      return;
    }

    // Prevent the creation of the user account.
    $event->cancelAutomaticRegistration();

    $this->messenger()->addStatus($this->t('Please register at <a href=":smed_url" target="_blank">:smed_url</a>', [
      ':smed_url' => $this->configFactory->get('eic_webservices.settings')->get('smed_url'),
    ]));

  }

  /**
   * React to a user trying to login with cas.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   Cas pre login event.
   */
  public function userPreLogin(CasPreLoginEvent $event) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $event->getAccount();

    // Update user information based on EU Login attributes.
    $properties = $event->getCasPropertyBag();
    $account->setEmail($properties->getAttribute('email'));
    $account->field_first_name->value = $properties->getAttribute('firstName');
    $account->field_last_name->value = $properties->getAttribute('lastName');
    $account->save();

    // We only check/sync user against SMED if account is not active.
    // This is to avoid too much load on the SMED.
    if (!$account->isActive() && $this->configFactory->get('eic_user_login.settings')->get('check_sync_user')) {

      // Fetch information from SMED.
      $data = [
        'email' => $account->getEmail(),
        'username' => $account->getAccountName(),
      ];

      // Check if we have a proper value for the SMED ID.
      if ($account->hasField('field_smed_id') && !$account->get('field_smed_id')->isEmpty()) {
        $data['user_dashboard_id'] = $account->field_smed_id->value;
      }

      if ($result = $this->smedUserConnection->queryEndpoint($data)) {
        // Update the user status.
        $this->smedUserManager->updateUserInformation($account, $result);
      }
    }

    try {
      $this->smedUserManager->isUserLoginAuthorised($account);
      $this->messenger()->addStatus($this->smedUserManager->getLoginMessage($account));
    }
    catch (SmedUserLoginException $e) {
      $event->cancelLogin($e->getUserMessage());
    }
  }

  /**
   * React on the pre user load event.
   *
   * Due to EIC specificities, we need to use the email address as cas username
   * instead of the real EU Login ID.
   * So we modify the returned property directly from after validation.
   *
   * @param \Drupal\cas\Event\CasPreUserLoadEvent $event
   *   Cas per user load event.
   */
  public function userPreLoad(CasPreUserLoadEvent $event) {
    $property_bag = $event->getCasPropertyBag();

    // Store the real username in a new property.
    $property_bag->setAttribute('_real_username', $property_bag->getUsername());

    // Replace the username with the email address.
    $property_bag->setUsername($property_bag->getAttribute('email'));
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
