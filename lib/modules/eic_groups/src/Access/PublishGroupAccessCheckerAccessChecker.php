<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * @DCG
 * To make use of this access checker add
 * '_publish_group_access_checker: Some value' entry to route definition under
 * requirements section.
 */
class PublishGroupAccessCheckerAccessChecker implements AccessInterface {

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

    $moderation_state = $group->get('moderation_state')->value;

    switch ($moderation_state) {
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
        // Users can only publish a group if the group is in "draft" state.
        // If the current user does not have permission to change group
        // moderation state to publish, we only allow access if the user is an
        // "administrator" or a "content_administrator".
        if (!$account->hasPermission('use groups transition publish')) {
          $access = AccessResult::allowedIf(in_array('administrator', $account->getRoles(TRUE)) ||
            !in_array('content_administrator', $account->getRoles(TRUE)))
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }
        else {
          $membership = $group->getMember($account);

          // If the user is not a member of the group, we only allow access if
          // the user is an "administrator" or "content_administrator".
          if (!$membership) {
            $access = AccessResult::allowedIf(in_array('administrator', $account->getRoles(TRUE)) || in_array('content_administrator', $account->getRoles(TRUE)))
              ->addCacheableDependency($account)
              ->addCacheableDependency($group);
          }
          else {
            // For group members we only allow access if the user is the group
            // owner or an "administrator" or a "content_administrator".
            $access = AccessResult::allowedIf(in_array('administrator', $account->getRoles(TRUE)) || in_array('content_administrator', $account->getRoles(TRUE)) ||
              in_array('group-owner', array_keys($membership->getRoles())))
              ->addCacheableDependency($account)
              ->addCacheableDependency($group);
          }
        }
        break;

      default:
        $access = AccessResult::forbidden()
          ->addCacheableDependency($group);
        break;
    }

    return $access;
  }

}
