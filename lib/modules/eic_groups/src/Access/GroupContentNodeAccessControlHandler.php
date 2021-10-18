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

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');
    $group_contents = $storage->loadByEntity($entity);

    if (empty($group_contents)) {
      return $access;
    }

    // Allow access to power users.
    if (UserHelper::isPowerUser($account)) {
      $access = GroupAccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity);
    }

    switch ($operation) {
      case 'view':
        $group_content = reset($group_contents);
        $group = $group_content->getGroup();
        $membership = $group->getMember($account);
        $moderation_state = $group->get('moderation_state')->value;

        // User is a group admin, so we allow access.
        if (EICGroupsHelper::userIsGroupAdmin($group, $account)) {
          $access = GroupAccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($membership)
            ->addCacheableDependency($entity);
          break;
        }

        // At this point it means the user is not a poweruser neither a group
        // admin. Therefore, If group is blocked no user other can view the its
        // content besides powerusers or group admins.
        if ($moderation_state !== GroupsModerationHelper::GROUP_PUBLISHED_STATE) {
          $access = AccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($membership)
            ->addCacheableDependency($group)
            ->addCacheableDependency($entity);
        }
        break;

      case 'update':
        // Always deny access to book pages.
        if ($entity->bundle() === 'book') {
          $access = GroupAccessResult::forbiddenIf(!$account->hasPermission('bypass node access'))
            ->cachePerUser();
          break;
        }

        // We check if the user is a member of a group where this node is
        // referenced and if so, we allow access to edit the node if the owner
        // allowed members to do so via "member_content_edit_access" property.
        foreach ($group_contents as $group_content) {
          $group = $group_content->getGroup();

          $editable_by_members = $entity->get(NodeProperty::MEMBER_CONTENT_EDIT_ACCESS)->value;

          if ($editable_by_members) {
            $membership = $group->getMember($account);

            $access = AccessResult::allowedIf($membership)
              ->addCacheableDependency($account)
              ->addCacheableDependency($membership)
              ->addCacheableDependency($entity);
            break;
          }
        }
        break;

    }

    return $access;
  }

}
