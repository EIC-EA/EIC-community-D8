<?php

namespace Drupal\eic_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The "my profile" controller.
 */
class MyProfileController extends ControllerBase {

  /**
   * The member activities endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The server request.
   *
   * @return array
   *   An empty array.
   */
  public function activity(Request $request): array {
    return [];
  }

  /**
   * Checks access to the group about page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An AccessResult object.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    return $account->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
