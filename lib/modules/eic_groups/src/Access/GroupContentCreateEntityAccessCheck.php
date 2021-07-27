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
   * The flag access check inner service.
   *
   * @var \Drupal\group\Access\GroupContentCreateEntityAccessCheck
   */
  protected $groupContentCreateEntityAccessCheck;

  /**
   * The user helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * Constructor.
   *
   * @param \Drupal\group\Access\GroupContentCreateEntityAccessCheck $group_content_create_entity_access_check_inner_service
   *   The flag access check inner service.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The user helper service.
   */
  public function __construct(
    GroupContentCreateEntityAccessCheckBase $group_content_create_entity_access_check_inner_service,
    UserHelper $user_helper
  ) {
    $this->groupContentCreateEntityAccessCheck = $group_content_create_entity_access_check_inner_service;
    $this->userHelper = $user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id) {
    $access = $this->groupContentCreateEntityAccessCheck->access($route, $account, $group, $plugin_id);

    // If access is allowed, we also need to check if the user can create group
    // content based on the current group moderation state.
    if ($access->isAllowed()) {
      switch ($group->get('moderation_state')->value) {
        case GroupsModerationHelper::GROUP_PENDING_STATE:
          // Deny access to the group content node creation form if the group
          // is in pending state and the user is not a "site_admin" or a
          // "content_administrator".
          if (!$this->userHelper->isPowerUser($account)) {
            $access = AccessResult::forbidden()
              ->addCacheableDependency($account)
              ->addCacheableDependency($group);
          }
          break;

      }
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
