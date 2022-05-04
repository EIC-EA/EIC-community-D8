<?php

namespace Drupal\eic_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
/**
 * Custom access check for the current user matching with route user parameter.
 */
class MatchCurrentUserRouteAccess implements AccessInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    /** @var \Drupal\user\UserInterface|NULL $route_user */
    $route_user = (int) $this->routeMatch->getParameter('user');

    return $route_user && (int) $account->id() === $route_user ?
      AccessResult::allowed() :
      AccessResult::forbidden();
  }

}
