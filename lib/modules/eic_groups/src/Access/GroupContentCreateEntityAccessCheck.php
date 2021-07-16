<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Access\GroupContentCreateEntityAccessCheck as GroupContentCreateEntityAccessCheckBase;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Extends access checker for group content target entity creation.
 */
class GroupContentCreateEntityAccessCheck extends GroupContentCreateEntityAccessCheckBase {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id) {
    $access = parent::access($route, $account, $group, $plugin_id);

    // If access is allowed, we also need to check if the user can create group
    // content based on the current group moderation state.
    if ($access->isAllowed()) {
      switch ($group->get('moderation_state')->value) {
        case GroupsModerationHelper::GROUP_PENDING_STATE:
          // Deny access to the group content node creation form if the group
          // is in pending state and the user is not a "site_admin" or a
          // "content_administrator".
          $access = AccessResult::forbiddenIf(!in_array(UserHelper::ROLE_SITE_ADMINISTRATOR, $account->getRoles(TRUE)) && !in_array(UserHelper::ROLE_CONTENT_ADMINISTRATOR, $account->getRoles(TRUE)))
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
          break;

      }
    }

    return $access;
  }

}
