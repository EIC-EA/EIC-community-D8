<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;

/**
 * Access check for various group related pages.
 */
class GroupPagesAccessChecker implements AccessInterface {

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  private $groupsHelper;

  /**
   * The constructor.
   *
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(EICGroupsHelperInterface $groups_helper) {
    $this->groupsHelper = $groups_helper;
  }

  /**
   * Access method.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The AccountProxy.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return the access result.
   */
  public function access(
    AccountProxy $account,
    GroupInterface $group = NULL
  ) {
    if (!$group) {
      return AccessResult::neutral();
    }

    // If group is blocked and user is not a power user, we deny access.
    if ($group->get('moderation_state')->value === GroupsModerationHelper::GROUP_BLOCKED_STATE
      && !UserHelper::isPowerUser($account)) {
      return AccessResult::forbidden()
        ->addCacheableDependency($account)
        ->addCacheableDependency($group);
    }

    return AccessResult::neutral();
  }

}
