<?php

namespace Drupal\eic_overviews\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides route response for group overview pages.
 */
class GroupOverviewsController extends ControllerBase {

  /**
   * Returns the content for a generic group overview page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function buildGenericPage(GroupInterface $group) {
    return ['#markup' => ''];
  }

  /**
   * Checks access to the group overview pages.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An AccessResult object.
   */
  public function access(RouteMatchInterface $route_match, GroupInterface $group, AccountInterface $account) {
    $overview_perm = NULL;

    switch ($route_match->getRouteName()) {
      case GroupOverviewPages::DISCUSSIONS:
        $overview_perm = 'access discussions overview';
        break;

      case GroupOverviewPages::EVENTS:
        $overview_perm = 'access events overview';
        break;

      case GroupOverviewPages::FILES:
        $overview_perm = 'access files overview';
        break;

      case GroupOverviewPages::LATEST_ACTIVITY:
        $overview_perm = 'access latest activity stream';
        break;

      case GroupOverviewPages::MEMBERS:
      case GroupOverviewPages::ORGANISATIONS_TEAM:
        $overview_perm = 'access members overview';
        break;

      case GroupOverviewPages::NEWS:
        $overview_perm = 'access news overview';
        break;

      case GroupOverviewPages::SEARCH:
        $overview_perm = 'access group search overview';
        break;

    }

    if (!$overview_perm) {
      return AccessResult::forbidden()
        ->addCacheableDependency($group)
        ->addCacheableDependency($account);
    }

    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, $overview_perm)
      ->addCacheableDependency($group)
      ->addCacheableDependency($account);
  }

}
