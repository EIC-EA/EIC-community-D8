<?php

namespace Drupal\eic_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The "my settings" controller.
 */
class MySettingsController extends ControllerBase
{

  /**
   * The member activities endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function settings(Request $request): array
  {
    return [];
  }

  /**
   * Checks access to the group about page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account)
  {
    return $account->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
