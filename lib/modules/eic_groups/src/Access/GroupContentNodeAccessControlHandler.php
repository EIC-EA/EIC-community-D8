<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\Constants\NodeProperty;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Plugin\GroupContentAccessControlHandler;

/**
 * Overrides group content access control handler for group_node plugins.
 */
class GroupContentNodeAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::entityAccess($entity, $operation, $account, $return_as_object);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    if (empty($group = \Drupal::service('eic_groups.helper')->getOwnerGroupByEntity($entity))) {
      return $access;
    }

    // Allow access to power users.
    if ($is_power_user = UserHelper::isPowerUser($account)) {
      $access = GroupAccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity);
    }

    switch ($operation) {
      case 'view':
        if ($is_power_user) {
          break;
        }

        $membership = $group->getMember($account);
        $moderation_state = $group->get('moderation_state')->value;

        // If user is a group admin, we allow access.
        if ($membership && EICGroupsHelper::userIsGroupAdmin($group, $account, $membership)) {
          $access = GroupAccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($membership)
            ->addCacheableDependency($entity);
          break;
        }

        // Check if user has access to the group, if not we deny access to the
        // node.
        if (!$group->access('view')) {
          $access = AccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($membership)
            ->addCacheableDependency($entity);
        }

        // At this point it means the user is not a poweruser neither a group
        // admin. Therefore, if group is blocked no user other can view its
        // content besides powerusers or group admins.
        if ($moderation_state === GroupsModerationHelper::GROUP_BLOCKED_STATE) {
          $access = AccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group)
            ->addCacheableDependency($entity);
        }

        if ($membership) {
          $access->addCacheableDependency($membership);
        }
        break;

      case 'update':
        // Always deny access to book pages.
        if ($entity->bundle() === 'book') {
          $access = GroupAccessResult::forbiddenIf(!$account->hasPermission('bypass node access'))
            ->cachePerUser();
          break;
        }

        // Allow access to power users.
        if ($is_power_user) {
          break;
        }

        // We check if the user is a member of the group where this node is
        // referenced and if so, we allow access to edit the node if the owner
        // allowed members to do so via "member_content_edit_access" property.
        $editable_by_members = $entity->get(NodeProperty::MEMBER_CONTENT_EDIT_ACCESS)->value;

        if ($editable_by_members) {
          $membership = $group->getMember($account);

          $access = AccessResult::allowedIf($membership)
            ->addCacheableDependency($account)
            ->addCacheableDependency($membership)
            ->addCacheableDependency($entity);
          break;
        }
        break;

      case 'delete':
        // Allow access to power users.
        if ($is_power_user) {
          break;
        }
        break;

    }

    return $access;
  }

}
