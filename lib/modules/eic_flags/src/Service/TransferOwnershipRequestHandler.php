<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;

/**
 * Service that provides logic for transfer ownership requests.
 *
 * @package Drupal\eic_flags\Service
 */
class TransferOwnershipRequestHandler extends AbstractRequestHandler {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return RequestTypes::TRANSFER_OWNERSHIP;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    // @todo Define message templates per request status.
    return [
      // RequestStatus::OPEN => 'notify_new_transfer_owner_request',
      // RequestStatus::DENIED => 'notify_transfer_owner_request_denied',
      // RequestStatus::ACCEPTED => 'notify_transfer_owner_request_accepted',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    switch ($content_entity->getEntityTypeId()) {
      case 'group_content':
        /** @var \Drupal\group\Entity\GroupContentInterface $content_entity */
        $this->transferGroupOwnership($content_entity->getGroup(), $content_entity);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlagPostSave(FlaggingInterface $flag) {
    // Invalidate flagged entity cache.
    Cache::invalidateTags($flag->getFlaggable()->getCacheTagsToInvalidate());
    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function canRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    // Default access.
    $access = AccessResult::forbidden();

    switch ($entity->getEntityTypeId()) {
      case 'group_content':
        $access = $this->canRequestGroupTransferOwnership($account, $entity);
        break;

    }

    return $access;
  }

  /**
   * Check if the group ownership transfer can be made by the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Currently logged in account, anonymous users are not allowed.
   * @param \Drupal\group\Entity\GroupContentInterface $entity
   *   The group content entity against the access check is made.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  private function canRequestGroupTransferOwnership(
    AccountInterface $account,
    GroupContentInterface $entity
  ) {
    $group = $entity->getGroup();

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($group);

    // We return access denied if the group content entity is not a group
    // membership.
    if ($entity->getContentPlugin()->getPluginId() !== 'group_membership') {
      return $access;
    }

    // We return access denied if there are open requests for this entity.
    if ($this->hasOpenRequest($entity, $account)) {
      return $access;
    }

    // If current user is not a group owner or a power user, we return
    // access forbidden.
    if (!(
      $account->id() === EICGroupsHelper::getGroupOwner($group)->id() ||
      UserHelper::isPowerUser($account)
    )) {
      return $access;
    }

    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $entity->getEntity();
    $membership = $group->getMember($new_owner);
    if (!$membership) {
      return $access;
    }

    $group_owner_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE;
    $group_admin_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE;

    // Allow access to transfer group ownership if the member is a group admin
    // but not the owner and if there are no open requests for the member.
    return AccessResult::allowedIf(
      !in_array($group_owner_role, array_keys($membership->getRoles())) &&
      in_array($group_admin_role, array_keys($membership->getRoles())))
      ->addCacheableDependency($entity)
      ->addCacheableDependency($group);
  }

  /**
   * {@inheritdoc}
   */
  public function canCloseRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    // Default access.
    $access = AccessResult::forbidden();

    switch ($entity->getEntityTypeId()) {
      case 'group_content':
        $access = $this->canCloseRequestGroupTransferOwnership($account, $entity);
        break;

    }

    return $access;
  }

  /**
   * Check if request ownership transfer can be closed by the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Currently logged in account, anonymous users are not allowed.
   * @param \Drupal\group\Entity\GroupContentInterface $entity
   *   The group content entity against the access check is made.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  private function canCloseRequestGroupTransferOwnership(
    AccountInterface $account,
    GroupContentInterface $entity
  ) {
    $group = $entity->getGroup();

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($group);

    // We return access denied if the group content entity is not a group
    // membership.
    if ($entity->getContentPlugin()->getPluginId() !== 'group_membership') {
      return $access;
    }

    // We return access denied if there are no requests for this entity.
    if (!$this->hasOpenRequest($entity, $account)) {
      return $access;
    }

    // If current user is not a group owner or a power user, we return
    // access forbidden.
    if (!(
      $entity->getEntity()->id() === $account->id() ||
      UserHelper::isPowerUser($account)
    )) {
      return $access;
    }

    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $entity->getEntity();
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
      ->addCacheableDependency($entity)
      ->addCacheableDependency($group);
  }

  /**
   * {@inheritdoc}
   */
  public function hasOpenRequest(
    ContentEntityInterface $content_entity,
    AccountInterface $user
  ) {
    return !empty($this->getOpenRequests($content_entity));
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    return [
      'group_content' => 'transfer_owner_request_group',
    ];
  }

  /**
   * Transfers the group ownership and redirect user to the previous page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity related to the new owner.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  private function transferGroupOwnership(GroupInterface $group, GroupContentInterface $group_content) {
    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $group_content->getEntity();
    $new_owner_membership = $group->getMember($new_owner);

    /** @var \Drupal\group\GroupMembership $old_owner_membership */
    $old_owner_membership = EICGroupsHelper::getGroupOwner($group, TRUE);

    // Removes group owner role from the old owner and add group admin role.
    $this->updateOldOwnerRoles($old_owner_membership);

    $group_owner_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE;
    // Transfer old group owner role to the new owner.
    $new_owner_membership->addRole($group_owner_role);
  }

  /**
   * Adds/removes roles from the old owner when transfering group ownership.
   *
   * @param \Drupal\group\GroupMembership $old_owner_membership
   *   The old group owner membership.
   */
  private function updateOldOwnerRoles(GroupMembership $old_owner_membership) {
    $group = $old_owner_membership->getGroup();

    $add_roles = [
      $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE,
    ];

    $group_content = $old_owner_membership->getGroupContent();

    // Add roles.
    foreach ($add_roles as $new_role) {
      $has_role = FALSE;
      // Check if a member already has the role.
      foreach ($group_content->group_roles as $key => $role_item) {
        if ($role_item->target_id === $new_role) {
          $has_role = TRUE;
          break;
        }
      }

      if ($has_role) {
        continue;
      }

      $group_content->group_roles[] = $new_role;
    }

    $remove_roles = [
      $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE,
    ];

    // Remove roles.
    foreach ($remove_roles as $old_role) {
      foreach ($group_content->group_roles as $key => $role_item) {
        if ($role_item->target_id == $old_role) {
          $group_content->group_roles->removeItem($key);
        }
      }
    }

    $group_content->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedResponsesForClosedRequests() {
    return [
      RequestStatus::DENIED,
      RequestStatus::ACCEPTED,
    ];
  }

}
