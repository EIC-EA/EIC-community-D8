<?php

namespace Drupal\eic_user\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_user\UserHelper;
use Drupal\profile\Entity\ProfileInterface;
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
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\eic_user\UserHelper $user_helper
   */
  public function __construct(AccountProxyInterface $current_user, UserHelper $user_helper) {
    $this->currentUser = $current_user;
    $this->userHelper = $user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    return strpos($route_name, 'eic_user.user.') !== FALSE || $route_name === 'entity.profile.edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $route_name = $route_match->getRouteName();
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $user = $route_match->getParameter('user');
    if (!$user instanceof UserInterface && $route_match->getParameter('profile')) {
      $profile = $route_match->getParameter('profile');
      if ($profile instanceof ProfileInterface) {
        $user = $profile->getOwner();
      }
    }

    if ($user instanceof UserInterface) {
      $links[] = Link::fromTextAndUrl(
        $this->t($this->userHelper->getFullName($user), [], ['context' => 'eic_user']),
        $user->toUrl()
      );
    }

    if (strpos($route_name, 'eic_user.user.') && 'eic_user.user.activity' !== $route_name) {
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
