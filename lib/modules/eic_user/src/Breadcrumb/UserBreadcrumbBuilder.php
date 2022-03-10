<?php

namespace Drupal\eic_user\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Provides a breadcrumb builder for users.
 */
class UserBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ContentBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return strpos($route_match->getRouteName(), 'eic_user.user.') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $user = $route_match->getParameter('user');

    if ($user instanceof UserInterface) {
      $links[] = Link::fromTextAndUrl(
        $this->t('My profile', [], ['context' => 'eic_user']),
        $user->toUrl()
      );
    }

    if ('eic_user.user.activity' !== $route_match->getRouteName()) {
      $links[] = Link::fromTextAndUrl(
        $this->t('My activity feed', [], ['context' => 'eic_user']),
        Url::fromRoute(
          'eic_user.user.activity',
          ['user' => $user->id()],
          ['context' => 'eic_user']
        )
      );
    }

    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path', 'user']);

    return $breadcrumb;
  }

}
