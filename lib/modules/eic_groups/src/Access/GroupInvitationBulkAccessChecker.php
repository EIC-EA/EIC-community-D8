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
 * Checks if user can access group invitation bulk routes.
 *
 * To make use of this access checker add '_group_invitation_bulk: Some value'
 * entry to route definition under requirements section.
 */
class GroupInvitationBulkAccessChecker implements AccessInterface {

  /**
   * Access callback.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group) {
    $can_invite = FALSE;
    $group_permission = $route->hasRequirement('_group_permission') ?
      $route->getRequirement('_group_permission') :
      FALSE;
    $is_group_admin = EICGroupsHelper::userIsGroupAdmin($group, $account);
    $membership = $group->getMember($account);

    // Allow access to power users.
    if (UserHelper::isPowerUser($account)) {
      return AccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($group);
    }

    $moderation_state = $group->get('moderation_state')->value;

    switch ($group->get('moderation_state')->value) {
      case GroupsModerationHelper::GROUP_PENDING_STATE:
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
      case GroupsModerationHelper::GROUP_BLOCKED_STATE:
      case GroupsModerationHelper::GROUP_ARCHIVED_STATE:
        // If the user is not a member of the group, access will be denied.
        if (!$membership) {
          break;
        }

        // We allow access if the user is the group owner or a group admin, and
        // moderation state is set to DRAFT.
        if (
          $moderation_state === GroupsModerationHelper::GROUP_DRAFT_STATE &&
          $is_group_admin
        ) {
          $can_invite = TRUE;
        }
        break;

      default:
        // Only group admins can invite multiple users.
        if ($is_group_admin) {
          $can_invite = TRUE;
        }
        break;

    }

    // If the user cannot invite or doesn't have the needed group permission,
    // we deny access.
    if (
      !$can_invite ||
      (
        $group_permission &&
        !$group->hasPermission($group_permission, $account)
      )
    ) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = AccessResult::allowed();
    }

    // Adds default cacheable dependencies.
    $access->addCacheableDependency($account)
      ->addCacheableDependency($group);

    // Adds membership as cacheable dependency.
    if ($membership) {
      $access->addCacheableDependency($membership);
    }

    return $access;
  }

}
