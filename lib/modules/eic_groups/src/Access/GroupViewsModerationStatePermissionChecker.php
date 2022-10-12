<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionAccessCheck;
use Drupal\views\Views;
use Symfony\Component\Routing\Route;

/**
 * Calculates group permissions + moderation state for an account.
 */
class GroupViewsModerationStatePermissionChecker extends GroupPermissionAccessCheck {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $route->setRequirement('_group_permission', $route->getRequirement('_group_views_moderation_state_permission_access_check'));

    $has_access = parent::access($route, $route_match, $account);

    $view_id = $route->getDefault('view_id');
    $display_id = $route->getDefault('display_id');
    if ($view_id && $display_id) {
      $view = Views::getView($view_id);
      if ($view) {
        $view->setDisplay($display_id);
        return $view->access($display_id) ? $has_access : AccessResult::forbidden();
      }
    }

    return $has_access;
  }

}
