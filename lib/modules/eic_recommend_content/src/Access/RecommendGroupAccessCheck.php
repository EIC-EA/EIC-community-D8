<?php

namespace Drupal\eic_recommend_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * To make use of this access checker add
 * '_eic_recommend_content_group: Some value' entry to route definition under
 * requirements section.
 */
class RecommendGroupAccessCheck implements AccessInterface {

  /**
   * The oec_group_flex.helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * Constructs a new RecommendGroupAccessCheck object.
   *
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The oec_group_flex.helper service.
   */
  public function __construct(OECGroupFlexHelper $oec_group_flex_helper) {
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * Checks routing access for the recommend group route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   (optional) A group object. If the $group is not specified, then access
   *   is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group = NULL) {
    if (!$group) {
      return AccessResult::forbidden();
    }

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($group);

    $visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);
    $allowed_group_visibilities = [
      'public',
      'custom_restricted',
      'restricted_community_members',
    ];

    if (!in_array($visibility_settings['plugin_id'], $allowed_group_visibilities)) {
      return $access;
    }

    $moderation_state = $group->get('moderation_state')->value;

    switch ($moderation_state) {
      case GroupsModerationHelper::GROUP_PUBLISHED_STATE:
        // Anonymous users cannot recommend groups.
        if ($account->isAnonymous()) {
          return $access;
        }

        // Power users can always recommend published groups.
        if (UserHelper::isPowerUser($account)) {
          $access = AccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
          break;
        }

        // At this point, it means the user is not a power user. If the current
        // user does not have permission to view the group, we deny access to
        // recommend it.
        if (!$group->access('view', $account)) {
          break;
        }

        $access = AccessResult::allowed()
          ->addCacheableDependency($account)
          ->addCacheableDependency($group);
        break;

    }

    return $access;
  }

}
