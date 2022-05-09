<?php

namespace Drupal\eic_user_login\EventSubscriber;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\eic_user\ProfileConst;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides an Event Subscriber for Request events.
 */
class RequestEventSubscriber implements EventSubscriberInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Lis of routes that should not be redirected.
   *
   * @var array
   */
  const ALLOWED_ROUTES = [
    ProfileConst::MEMBER_PROFILE_EDIT_ROUTE_NAME,
    'user.logout',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new RequestEventSubscriber instance.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The Private temp store factory.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The current user.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    AccountProxy $account
  ) {
    $this->tempStore = $temp_store_factory->get('eic_user_login');
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'checkUserProfile',
    ];
  }

  /**
   * Checks on each request if user profile is completed and redirect if needed.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function checkUserProfile(RequestEvent $event): void {
    // We skip AJAX requests.
    if ($event->getRequest()->isXmlHttpRequest()) {
      return;
    }

    // We skip request made through CLI.
    if (PHP_SAPI === 'cli') {
      return;
    }

    // We skip non-authenticated users.
    if (!$this->currentUser->isAuthenticated()) {
      return;
    }

    // We skip allowed routes.
    if ($this->isAllowedRoute($event->getRequest()->get('_route'))) {
      return;
    }

    // Check if user profile is completed.
    if ($this->tempStore->get('is_profile_completed') === FALSE) {
      // Redirect the user to their member profile.
      $route_parameters = [
        'user' => $this->currentUser->id(),
        'profile_type' => ProfileConst::MEMBER_PROFILE_TYPE_NAME,
      ];
      $url = Url::fromRoute(ProfileConst::MEMBER_PROFILE_EDIT_ROUTE_NAME, $route_parameters);
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);

      // Print a message to the user.
      $message = $this->t("Don't forget to complete your profile - Your profile says a lot about who you are and helps other community members recognize your expertise.", [], ['context' => 'eic_user_login']);
      $this->messenger()->addWarning($message);
    }
  }

  /**
   * Defines if the given route is allowed.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return bool
   *   TRUE if route is allowed, FALSE otherwise.
   */
  protected function isAllowedRoute(string $route_name): bool {
    if (in_array($route_name, self::ALLOWED_ROUTES)) {
      return TRUE;
    }

    return FALSE;
  }

}
