<?php

namespace Drupal\eic_groups\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation as GroupInvitationBase;

/**
 * Extends content enabler class for group invitations.
 */
class GroupInvitation extends GroupInvitationBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = parent::getGroupOperations($group);
    $account = \Drupal::currentUser();

    // We keep only operations the user has access to.
    foreach ($operations as $key => $operation) {
      if (!$operation['url']->access($account)) {
        unset($operations[$key]);
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    $access = parent::createAccess($group, $account);

    // If access is not allowed, we do nothing.
    if (!$access->isAllowed()) {
      return $access;
    }

    switch ($group->get('moderation_state')->value) {
      case GroupsModerationHelper::GROUP_PENDING_STATE:
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
        // Deny access to the group invitation form if the group is NOT yet
        // published and the user is not a "site_admin" or a
        // "content_administrator".
        if (!UserHelper::isPowerUser($account)) {
          $access = GroupAccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }
        break;

    }

    return $access;
  }

}
