<?php

namespace Drupal\eic_groups\Access;

use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Access\GroupPermissionAccessCheck;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on permissions defined via
 * $module.group_permissions.yml files.
 */
class SmedGroupPermissionAccessCheck extends GroupPermissionAccessCheck {

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
    $permission = $route->getRequirement('_smed_group_permission_access_check');

    // Don't interfere if no permission was specified.
    if ($permission === NULL) {
      return AccessResult::neutral();
    }

    // Don't interfere if no group was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    // User cannot leave from SMED groups.
    if (EICGroupsHelper::isSmedGroup($group)) {
      return AccessResult::forbidden()
        ->addCacheableDependency($account)
        ->addCacheableDependency($group);
    }

    return parent::access($route, $route_match, $account);
  }

}
