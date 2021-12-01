<?php

namespace Drupal\eic_group_membership\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * To make use of this access checker add
 * '_transfer_ownership_access_checker: Some value' entry to route definition
 * under requirements section.
 */
class TransferGroupOwnershipAccessChecker implements AccessInterface {

  /**
   * Checks routing access for the transfer ownership route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   (optional) A group object. If the $group is not specified, then access
   *   is denied.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   (optional) A group content object. If the $group_content is not
   *   specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group = NULL, GroupContentInterface $group_content = NULL) {
    if (!$group || !$group_content) {
      return AccessResult::forbidden();
    }

    if ($group_content->getContentPlugin()->getPluginId() !== 'group_membership') {
      return AccessResult::forbidden();
    }

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($group);

    $is_admin = EICGroupsHelper::userIsGroupAdmin($group, $account) || UserHelper::isPowerUser($account);

    // If current user is not a group admin, we return access forbidden.
    if (!$is_admin) {
      return $access;
    }

    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $group_content->getEntity();
    $membership = $group->getMember($new_owner);
    if (!$membership) {
      return $access;
    }

    $group_owner_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE;
    $group_admin_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE;
    // Allow access to transfer group ownership if the member is a group admin
    // but not the owner.
    return AccessResult::allowedIf(
      !in_array($group_owner_role, array_keys($membership->getRoles())) &&
      in_array($group_admin_role, array_keys($membership->getRoles())))
      ->addCacheableDependency($group_content)
      ->addCacheableDependency($group);
  }

}
