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

/**
 * Access check for various group related view pages.
 */
class GroupPageAccessChecker implements AccessInterface {

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
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
    RouteMatchInterface $route_match,
    AccountProxy $account,
    GroupInterface $group = NULL
  ) {
    if (!$group) {
      return AccessResult::neutral();
    }

    // Set a default access to neutral before other checks.
    $access = AccessResult::neutral();

    if ($route_name = $route_match->getRouteName()) {
      switch ($route_name) {
        case 'entity.group.edit_form':
          $access = $this->accessGroupEditFormPage($account, $group);
          break;

        default:
          $access = $this->accessGroupCommonPages($account, $group);
          break;
      }
    }

    $access->addCacheableDependency($account)
      ->addCacheableDependency($group);

    if ($membership = $group->getMember($account)) {
      $access->addCacheableDependency($membership);
    }

    return $access;
  }

  /**
   * Access check method for group common pages.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The user account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return the access result.
   */
  public function accessGroupCommonPages(AccountProxy $account, GroupInterface $group) {
    if (GroupsModerationHelper::isBlocked($group)) {
      $access = AccessResult::forbidden();
      // If group is blocked and user is a power user or a group admin, we
      // allow access.
      if (UserHelper::isPowerUser($account) || EICGroupsHelper::userIsGroupAdmin($group, $account)) {
        $access = AccessResult::allowed();
      }
    }
    else {
      $access = AccessResult::allowed();
    }
    if (EICGroupsHelper::groupIsSensitive($group, $account)) {
      $access = AccessResult::forbidden();
  }
    return $access;
  }

  /**
   * Access check method for group edit page.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The user account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return the access result.
   */
  public function accessGroupEditFormPage(AccountProxy $account, GroupInterface $group) {
    $access = AccessResult::allowed();
    // GO/GA users cannot edit the group when being blocked.
    if (GroupsModerationHelper::isBlocked($group) && !UserHelper::isPowerUser($account)) {
      $access = AccessResult::forbidden();
    }
    if (EICGroupsHelper::groupIsSensitive($group, $account)) {
      $access = AccessResult::forbidden();
  }
    return $access;
  }

}
