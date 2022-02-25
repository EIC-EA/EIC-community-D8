<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for various group related view pages.
 */
class GroupViewPageAccessChecker implements AccessInterface {

  /**
   * The route match service.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Access method.
   *
   * @param Symfony\Component\Routing\Route $route
   *   The route to check.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The AccountProxy.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return the access result.
   */
  public function access(
    Route $route,
    AccountProxy $account,
    GroupInterface $group = NULL
  ) {
    $view_id = $route->getDefault('view_id');
    $display_id = $route->getDefault('display_id');

    // Ensure we are accessing a view page. Otherwise we return access neutral.
    if ($this->routeMatch->getRouteName() !== "view.$view_id.$display_id") {
      return AccessResult::neutral();
    }

    // Ensure we are accessing a view page of a group.
    if (!$group) {
      return AccessResult::neutral();
    }

    switch ($group->get('moderation_state')->value) {
      case GroupsModerationHelper::GROUP_BLOCKED_STATE:
        // If group is blocked and user is not a power user or a group admin,
        // we deny access.
        if (
          !UserHelper::isPowerUser($account) &&
          !EICGroupsHelper::userIsGroupAdmin($group, $account)
        ) {
          return AccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }
        break;

    }

    $access = AccessResult::allowed()
      ->addCacheableDependency($account)
      ->addCacheableDependency($group);

    if ($membership = $group->getMember($account)) {
      $access->addCacheableDependency($membership);
    }

    // We return access allowed but other modules may alter the final access.
    return $access;
  }

}
