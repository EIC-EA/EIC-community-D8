<?php

namespace Drupal\oec_group_flex\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * @DCG
 * To make use of this access checker add '_group_membership_request_access_check: Some value' entry to route
 * definition under requirements section.
 */
class GroupMembershipRequestAccessChecker implements AccessInterface {

  /**
   * The OEC Group Flex Helper.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(OECGroupFlexHelper $oec_group_flex_helper) {
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * Access callback.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group) {
    return AccessResult::allowedIf(
      $group->access('view', $account) &&
      !$this->oecGroupFlexHelper->getMembershipRequest($account, $group)
    )
    ->addCacheableDependency($group)
    ->addCacheableDependency($account);
  }

}
