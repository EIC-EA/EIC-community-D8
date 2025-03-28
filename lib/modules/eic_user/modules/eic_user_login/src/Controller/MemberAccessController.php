<?php

namespace Drupal\eic_user_login\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route response the member access page.
 */
class MemberAccessController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $currentRequest;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Http\RequestStack $request_stack
   *   The current request.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The current request.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('config.factory'),
    );
  }

  /**
   * Returns the content for member access page.
   *
   * @return array
   *   A renderable array.
   */
  public function build() {
    // Get the destination parameter if there is one.
    $destination = NULL;
    if (!empty($this->currentRequest->get('destination'))) {
      $destination = $this->currentRequest->get('destination');
    }

    // If user is already authenticated, there's no need for this page.
    // Redirect to the homepage.
    if ($this->currentUser->isAuthenticated()) {
      if (empty($destination)) {
        $url = Url::fromRoute('<front>')->toString();
      }
      else {
        $url = $destination;
      }

      return new RedirectResponse($url);
    }

    $login_url = Url::fromRoute('cas.login', [], [
      'attributes' => [
        'class' => ['cas-login-link'],
      ],
    ]);

    $register_url = $this->configFactory->get('eic_user_login.settings')->get('user_registration_url');
    $register_url = Url::fromUri($register_url, [
      'attributes' => [
        'class' => ['cas-register-link'],
        'target' => '_blank',
      ],
    ]);

    if (!empty($destination)) {
      // For the login link, we use the special returnto query param that is
      // handled by cas module.
      $login_url->setRouteParameter('returnto', $destination);
      $register_url->setOption('query', ['destination' => $destination]);
    }

    return [
      '#theme' => 'member_access_page',
      '#login_link' => Link::fromTextAndUrl($this->t('Log in'), $login_url),
      '#register_link' => Link::fromTextAndUrl($this->t('Register'), $register_url),
    ];
  }

}
