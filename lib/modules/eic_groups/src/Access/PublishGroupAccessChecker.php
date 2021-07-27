<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * To make use of this access checker add
 * '_publish_group_access_checker: Some value' entry to route definition under
 * requirements section.
 */
class PublishGroupAccessChecker implements AccessInterface {

  /**
   * The user helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * Constructs a new PublishGroupAccessChecker instance.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The user helper service.
   */
  public function __construct(UserHelper $user_helper) {
    $this->userHelper = $user_helper;
  }

  /**
   * Checks routing access for the publish group route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   (optional) A group object. If the $group is not specified, then access
   *   is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group = NULL) {
    if (!$group) {
      return AccessResult::forbidden();
    }

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($group);

    $moderation_state = $group->get('moderation_state')->value;

    switch ($moderation_state) {
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
        // Users can only publish a group if the group is in "draft" state.
        // If the user is a "site_admin" or "content_administrator" we always
        // allow access.
        if ($this->userHelper->isPowerUser($account)) {
          $access = AccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
          break;
        }

        // At this point, it means the user is not an admin. If the current
        // user does not have permission to change group moderation state to
        // publish, we deny access. Even group owners need this permission in
        // order to be able to publish their own groups.
        if (!$account->hasPermission('use groups transition publish')) {
          break;
        }

        $membership = $group->getMember($account);

        // If the user is not a member of the group, we deny access.
        if (!$membership) {
          break;
        }

        // For group members we only allow access if the user is the group
        // owner.
        $access = AccessResult::allowedIf(in_array(EICGroupsHelper::GROUP_OWNER_ROLE, array_keys($membership->getRoles())))
          ->addCacheableDependency($account)
          ->addCacheableDependency($group);
        break;

    }

    return $access;
  }

}
