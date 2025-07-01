<?php

namespace Drupal\eic_user\EventSubscriber;

use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * @see https://github.com/openeuropa/oe_authentication/pull/220
 */
class AlterEcasTfaAuthenticationEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ConfigFactoryInterface     $configFactory,
    protected readonly ExecutableManagerInterface $conditionManager,
    protected readonly ContextHandlerInterface    $contextHandler,
    TranslationInterface                          $stringTranslation,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[CasPreUserLoadRedirectEvent::class] = 'redirectEcasUser2fa';
    return $events;
  }

  /**
   * Checks if two-factor authentication is required and used for the login.
   *
   * @param \Drupal\cas\Event\CasPreUserLoadRedirectEvent $event
   *   The pre-login event.
   */
  public function redirectEcasUser2fa(CasPreUserLoadRedirectEvent $event) {
    // If the login has been executed with a two-factor authentication method,
    // there is no need for further checks.
    if ($this->isLoginWithTwoFactorAuthentication($event)) {
      return;
    }

    $config = $this->configFactory->get('oe_authentication.settings');

    $conditions_configuration = $config->get('2fa_conditions') ?? [];
    $email = $event->getPropertyBag()->getAttribute('email');
    $userEntityStorage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\Entity\User[] $userByEmail */
    $userByEmail = $userEntityStorage->loadByProperties(['mail' => $email]);
    try {
      if (!empty($userByEmail)) {
        $user = end($userByEmail);
        if ($this->isTwoFactorAuthenticationRequiredForUser($user, $conditions_configuration)) {
          $event->stopPropagation();
          $redirect = new TrustedRedirectResponse(Url::fromRoute('cas.login', options: [
            'query' => [
              // @see \Drupal\oe_authentication\Event\EuLoginEventSubscriber::forceTwoFactorAuthentication()
              'force_2fa' => 1,
            ],
          ])->toString());
          $event->setRedirectResponse($redirect);
        }
      }
    } catch (\Throwable $exception) {
      // If any exception happens, we cannot trust the login attempt anymore.
      // Use the default error message from the CAS module.
    }
  }


  /**
   * Checks if the login has been executed with a two-factor authentication.
   *
   * @param \Drupal\cas\Event\CasPreUserLoadRedirectEvent $event
   *   The pre-login event.
   *
   * @return bool
   *   True if 2FA has been used, false otherwise.
   */
  protected function isLoginWithTwoFactorAuthentication(CasPreUserLoadRedirectEvent $event): bool {
    return in_array(
      $event->getPropertyBag()->getAttribute('authenticationLevel'),
      ['MEDIUM', 'HIGH'],
    );
  }

  /**
   * Returns if two-factor authentication is required for a user account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account being logged in.
   * @param array[] $conditions_configuration
   *   The conditions' configuration.
   *
   * @return bool
   *   TRUE if 2FA should be required for this account login, FALSE otherwise.
   *
   * @throws \Throwable
   *   The method does not catch any exceptions thrown during plugin execution.
   */
  protected function isTwoFactorAuthenticationRequiredForUser(UserInterface $user, array $conditions_configuration): bool {
    // If no conditions are present, 2FA is not required.
    if (empty($conditions_configuration)) {
      return FALSE;
    }

    $contexts = [
      'user' => EntityContext::fromEntity($user),
    ];

    foreach ($conditions_configuration as $id => $configuration) {
      /** @var \Drupal\Core\Condition\ConditionInterface $plugin */
      $plugin = $this->conditionManager->createInstance($id, $configuration);
      if ($plugin instanceof ContextAwarePluginInterface) {
        $this->contextHandler->applyContextMapping($plugin, $contexts);
      }

      // Reject the login as soon as a condition plugin matches the user
      // account.
      if ($this->conditionManager->execute($plugin)) {
        return TRUE;
      }
    }

    return FALSE;
  }


}
