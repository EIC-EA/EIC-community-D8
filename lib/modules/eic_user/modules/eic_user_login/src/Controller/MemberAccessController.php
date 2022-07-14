<?php

namespace Drupal\eic_user_login\Controller;

use Drupal\Core\Controller\ControllerBase;
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
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Returns the content for member access page.
   *
   * @return array
   *   A renderable array.
   */
  public function build() {
    // If user is already authenticated, there's no need for this page.
    // Redirect to the homepage.
    if ($this->currentUser->isAuthenticated()) {
      $url = Url::fromRoute('<front>');
      return new RedirectResponse($url->toString());
    }

    $login_url = new Url('cas.login', [], [
      'attributes' => [
        'class' => ['cas-login-link'],
      ],
    ]);
    $register_url = new Url('cas.login', [], [
      'attributes' => [
        'class' => ['cas-login-link'],
        'target' => '_blank',
      ],
    ]);

    return [
      '#theme' => 'member_access_page',
      '#login_link' => Link::fromTextAndUrl($this->t('Log in'), $login_url),
      '#register_link' => Link::fromTextAndUrl($this->t('Register'), $register_url),
    ];
  }

}
