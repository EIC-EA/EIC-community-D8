<?php

namespace Drupal\eic_groups\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Access\GroupAccessResult;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation as GroupInvitationBase;

/**
 * Extends content enabler class for group invitations.
 */
class GroupInvitation extends GroupInvitationBase {

  const INVITATION_REMINDER_MAX_COUNT = 3;

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

      // Always add an anchor for mobile, so we scroll directly on the form.
      if ('invite-user' === $key) {
        /** @var \Drupal\Core\Url $url */
        $url = $operation['url'];
        $url->setOption('fragment', 'group-content-group-group-invitation-add-form');
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = parent::createAccess($group, $account);
    $is_power_user = UserHelper::isPowerUser($account);
    $is_group_admin = EICGroupsHelper::userIsGroupAdmin($group, $account);
    $user_can_invite = $group->hasField('field_group_invite_members') ?
      (int) $group->get('field_group_invite_members')->value :
      TRUE;
    $membership = $group->getMember($account);

    // If access is not allowed, we do nothing.
    if (!$access->isAllowed()) {
      return $access;
    }

    // Adds default cacheable dependencies.
    $access->addCacheableDependency($account)
      ->addCacheableDependency($group);

    // If access is allowed by default, we need to make sure the option to
    // invite users is enabled and also if the user is a poweruser or a
    // member of the group. Otherwise we force access denied as default before
    // moving further to the next conditions.
    if (
      $access->isAllowed() &&
      !$is_power_user &&
      (!$user_can_invite || ($user_can_invite && !$membership))
    ) {
      $access = GroupAccessResult::forbidden()
        ->addCacheableDependency($account)
        ->addCacheableDependency($group);
    }

    $moderation_state = $group->get('moderation_state')->value;

    switch ($moderation_state) {
      case GroupsModerationHelper::GROUP_PENDING_STATE:
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
      case GroupsModerationHelper::GROUP_BLOCKED_STATE:
      case GroupsModerationHelper::GROUP_ARCHIVED_STATE:
        // Deny access to the group invitation form if the group is NOT yet
        // published and the user is not a "site_admin" or a
        // "content_administrator".
        if (!$is_power_user) {
          $access = GroupAccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }

        // If the user is not a member of the group, we do nothing.
        if (!$membership) {
          break;
        }

        // We allow access if the user is the group owner or a group admin, and
        // moderation state is set to DRAFT.
        if (
          $moderation_state === GroupsModerationHelper::GROUP_DRAFT_STATE &&
          $is_group_admin &&
          $user_can_invite
        ) {
          $access = GroupAccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }
        break;

    }

    return $access;
  }

}
