<?php

namespace Drupal\eic_flags\Service;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service that provides logic for transfer ownership requests.
 *
 * @package Drupal\eic_flags\Service
 */
class TransferOwnershipRequestHandler extends AbstractRequestHandler {

  /**
   * The Solr document processor service.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor|NULL $solr_document_processor
   *   The EIC Search Solr Document Processor.
   */
  public function setDocumentProcessor(?SolrDocumentProcessor $solr_document_processor) {
    $this->solrDocumentProcessor = $solr_document_processor;
  }

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
    return [
      RequestStatus::OPEN => 'notify_new_transfer_owner_req',
      RequestStatus::DENIED => 'notify_transfer_owner_req_denied',
      RequestStatus::ACCEPTED => 'notify_transfer_owner_req_accept',
      RequestStatus::TIMEOUT => 'notify_transf_owner_expire',
      RequestStatus::CANCELLED => 'notify_transfer_owner_req_cancel',
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
        if ($content_entity->getContentPlugin()->getPluginId() !== 'group_membership') {
          break;
        }

        $group = $content_entity->getGroup();

        // If new owner id, we need to reindex entities from group.
        $this->solrDocumentProcessor->reIndexEntitiesFromGroup($group);
        $this->transferGroupOwnership($group, $content_entity);
        // Invalidate flagged entity cache.
        Cache::invalidateTags($content_entity->getCacheTagsToInvalidate());
        $this->invalidateGroupMembershipAdminCaches($group);
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function deny(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    // Invalidate flagged entity cache.
    Cache::invalidateTags($content_entity->getCacheTagsToInvalidate());

    switch ($content_entity->getEntityTypeId()) {
      case 'group_content':
        /** @var \Drupal\group\Entity\GroupContentInterface $content_entity */
        if ($content_entity->getContentPlugin()->getPluginId() !== 'group_membership') {
          break;
        }

        $this->invalidateGroupMembershipAdminCaches($content_entity->getGroup());
        break;

    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    return $this->deny($flagging, $content_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlagAlter(FlaggingInterface $flag) {
    switch ($flag->getFlaggable()->getEntityTypeId()) {
      case 'group_content':
        $flag->set('field_new_owner_ref', $flag->getFlaggable()->getEntity()->id());
        break;

      default:
        $flag->set('field_new_owner_ref', $flag->getFlaggable()->getOwnerId());
        break;
    }

    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlagPostSave(FlaggingInterface $flag) {
    $entity = $flag->getFlaggable();
    // Invalidate flagged entity cache.
    Cache::invalidateTags($flag->getFlaggable()->getCacheTagsToInvalidate());

    switch ($entity->getEntityTypeId()) {
      case 'group_content':
        /** @var \Drupal\group\Entity\GroupContentInterface $entity */
        if ($entity->getContentPlugin()->getPluginId() !== 'group_membership') {
          break;
        }

        $this->invalidateGroupMembershipAdminCaches($entity->getGroup());
        break;

    }

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

    /** @var \Drupal\group\GroupMembership[] $group_memberships */
    $group_memberships = EICGroupsHelper::getGroupAdmins($group);

    if (!$group_memberships) {
      return $access;
    }

    // We return access denied if there are open requests for at least one
    // group admin.
    foreach ($group_memberships as $group_membership) {
      if ($group_membership->getGroupContent()->id() === $entity->id()) {
        continue;
      }
      if ($this->hasOpenRequest($group_membership->getGroupContent(), $account)) {
        return $access;
      }
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
    if (
      !in_array($group_owner_role, array_keys($membership->getRoles())) &&
      in_array($group_admin_role, array_keys($membership->getRoles()))
    ) {
      $access = AccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity)
        ->addCacheableDependency($group);
    }

    return $access;
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
      ->addCacheableDependency($entity)
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

    $requests = $this->getOpenRequests($entity);
    $request = reset($requests);
    $expiration_date = 0;

    // If request has expiration, we set a max-age.
    if ($this->hasExpiration($request)) {
      $expiration_date = $request->get(HandlerInterface::REQUEST_TIMEOUT_FIELD)->value * 86400;
      $expiration_date += $request->get('created')->value;
      $access->setCacheMaxAge($expiration_date);
    }

    if ($this->hasExpired($request)) {
      return $access;
    }

    // If current user is a group owner, we return access forbidden.
    if (
      $account->id() === EICGroupsHelper::getGroupOwner($group)->id()
    ) {
      return $access;
    }

    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $entity->getEntity();
    $membership = $group->getMember($new_owner);
    if (!$membership || $new_owner->id() !== $account->id()) {
      return $access;
    }

    $group_owner_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE;
    $group_admin_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE;

    // Allow access to transfer group ownership if the member is a group admin
    // but not the owner.
    if (
      !in_array($group_owner_role, array_keys($membership->getRoles())) &&
      in_array($group_admin_role, array_keys($membership->getRoles()))
    ) {
      $access = AccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity)
        ->addCacheableDependency($group);
    }

    // Set max-age based on expiration date.
    if ($expiration_date > 0) {
      $access->setCacheMaxAge($expiration_date);
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function canCancelRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    // Default access.
    $access = AccessResult::forbidden();

    switch ($entity->getEntityTypeId()) {
      case 'group_content':
        $access = $this->canCancelRequestGroupTransferOwnership($account, $entity);
        break;

    }

    return $access;
  }

  /**
   * Check if request ownership transfer can be cancelled by the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Currently logged in account, anonymous users are not allowed.
   * @param \Drupal\group\Entity\GroupContentInterface $entity
   *   The group content entity against the access check is made.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  private function canCancelRequestGroupTransferOwnership(
    AccountInterface $account,
    GroupContentInterface $entity
  ) {
    $group = $entity->getGroup();

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($entity)
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

    $requests = $this->getOpenRequests($entity);
    // Only one request is open at a time, therefore we grab the first one we
    // found.
    $request = reset($requests);
    $expiration_date = 0;

    // If request has expiration, we set a max-age.
    if ($this->hasExpiration($request)) {
      $expiration_date = $request->get(HandlerInterface::REQUEST_TIMEOUT_FIELD)->value * 86400;
      $expiration_date += $request->get('created')->value;
      $access->setCacheMaxAge($expiration_date);
    }

    if ($this->hasExpired($request)) {
      return $access;
    }

    // Allow access to cancel the request if the current account is a power
    // user and the requested user corresponds to a different account.
    if (
      UserHelper::isPowerUser($account) &&
      $entity->getEntity()->id() !== $account->id()
    ) {
      return AccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity)
        ->addCacheableDependency($group);
    }

    /** @var \Drupal\user\UserInterface $membership */
    $membership = $group->getMember($account);
    if (!$membership) {
      return $access;
    }

    $group_owner_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE;

    // Allow access to cancel group ownership if current user is a group owner.
    if (
      in_array(
        $group_owner_role,
        array_keys($membership->getRoles())
      )
    ) {
      $access = AccessResult::allowed()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity)
        ->addCacheableDependency($group);
    }

    // Set max-age based on expiration date.
    if ($expiration_date > 0) {
      $access->setCacheMaxAge($expiration_date);
    }

    return $access;
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
   */
  private function transferGroupOwnership(GroupInterface $group, GroupContentInterface $group_content) {
    /** @var \Drupal\group\GroupMembership $old_owner_membership */
    $old_owner_membership = EICGroupsHelper::getGroupOwner($group, TRUE);

    // Removes group owner role from the old owner and adds group admin role.
    $this->updateMembershipRoles(
      $old_owner_membership,
      [
        $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE,
      ],
      [
        $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE,
      ]
    );

    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $group_content->getEntity();
    $new_owner_membership = $group->getMember($new_owner);

    // Transfer group owner role to the new owner and deletes group admin role.
    $this->updateMembershipRoles(
      $new_owner_membership,
      [
        $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE,
      ],
      [
        $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE,
      ]
    );
  }

  /**
   * Adds/removes roles from the group membership.
   *
   * @param \Drupal\group\GroupMembership $membership
   *   The old group owner membership.
   * @param array $add_roles
   *   Roles to add to the membership.
   * @param array $delete_roles
   *   Roles to delete from the membership.
   */
  public function updateMembershipRoles(GroupMembership $membership, array $add_roles = [], array $delete_roles = []) {
    $group_content = $membership->getGroupContent();

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

    // Remove roles.
    foreach ($delete_roles as $old_role) {
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
      RequestStatus::CANCELLED,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getActions(ContentEntityInterface $entity) {
    $actions = parent::getActions($entity);
    $actions['cancel_request'] = [
      'title' => $this->t('Cancel request to transfer ownership'),
      'url' => $entity->toUrl('close-request')
        ->setRouteParameter('request_type', $this->getType())
        ->setRouteParameter('response', RequestStatus::CANCELLED)
        ->setRouteParameter('destination', $this->currentRequest->getRequestUri()),
    ];
    return $actions;
  }

  /**
   * Invalidates cache tags for group administrators.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  private function invalidateGroupMembershipAdminCaches(GroupInterface $group) {
    /** @var \Drupal\group\GroupMembership[] $group_memberships */
    $group_memberships = EICGroupsHelper::getGroupAdmins($group);

    if (!$group_memberships) {
      return;
    }

    foreach ($group_memberships as $group_membership) {
      // Invalidate flagged entity cache.
      Cache::invalidateTags($group_membership->getGroupContent()->getCacheTagsToInvalidate());
    }
  }

}
