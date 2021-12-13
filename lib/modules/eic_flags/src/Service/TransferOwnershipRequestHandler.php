<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContentInterface;

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
        /** @var \Drupal\group\Entity\GroupInterface $content_entity */
        // $this->deleteGroup($content_entity);
        break;
    }
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
      !$this->getOpenRequests($entity) &&
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

}
