<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\EICGroupsHelperInterface;
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
   * The flag access check inner service.
   *
   * @var \Drupal\group\Access\GroupContentCreateEntityAccessCheck
   */
  protected $groupContentCreateEntityAccessCheck;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\group\Access\GroupContentCreateEntityAccessCheck $group_content_create_entity_access_check_inner_service
   *   The flag access check inner service.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    GroupContentCreateEntityAccessCheckBase $group_content_create_entity_access_check_inner_service,
    EICGroupsHelperInterface $eic_groups_helper,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->groupContentCreateEntityAccessCheck = $group_content_create_entity_access_check_inner_service;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id) {
    $access = $this->groupContentCreateEntityAccessCheck->access($route, $account, $group, $plugin_id);

    // Edge case where we make sure that no user (even user 1 or a user with
    // Drupal administrator role) can create new book pages inside groups.
    // There should only be 1 book page per group and it's automatically
    // created after creating the group.
    if ($plugin_id === 'group_node:book') {
      if ($group_book_nid = $this->eicGroupsHelper->getGroupBookPage($group)) {
        // We need to load the group book node in order to add it as a
        // cacheable dependency of the access result object.
        $group_book_node = $this->entityTypeManager->getStorage('node')->load($group_book_nid);

        return AccessResult::forbidden()
          ->addCacheableDependency($group_book_node)
          ->addCacheableDependency($group);
      }
    }

    // If access is not allowed, only powerusers can create content in the
    // group.
    if (!$access->isAllowed()) {
      return AccessResult::allowedIf(UserHelper::isPowerUser($account))
        ->addCacheableDependency($account)
        ->addCacheableDependency($group);
    }

    // If access is allowed, we also need to check if the user can create group
    // content based on the current group moderation state.
    switch ($group->get('moderation_state')->value) {
      case GroupsModerationHelper::GROUP_PENDING_STATE:
        // Deny access to the group content node creation form if the group
        // is in pending state and the user is not a "site_admin" or a
        // "content_administrator".
        if (!UserHelper::isPowerUser($account)) {
          $access = AccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }
        break;

      case GroupsModerationHelper::GROUP_BLOCKED_STATE:
        // Only Powerusers can create group content in blocked group.
        $access = AccessResult::allowedIf(UserHelper::isPowerUser($account))
          ->addCacheableDependency($account)
          ->addCacheableDependency($group);
        break;

    }

    return $access;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array(
      [$this->groupContentCreateEntityAccessCheck, $method],
      $args
    );
  }

}
