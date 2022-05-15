<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Class ArchivedRouteAccessCheck
 *
 * @package Drupal\eic_groups\Access
 */
class ArchivedRouteAccessCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountProxy $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(RouteMatchInterface $route_match, AccountProxy $account) {
    $group = $route_match->getParameter('group');
    $node = $route_match->getParameter('node');

    if ($node instanceof NodeInterface) {
      $group_contents = \Drupal::entityTypeManager()->getStorage('group_content')->loadByEntity($node);

      if (empty($group_contents)) {
        return AccessResult::allowed()->addCacheableDependency($node);
      }

      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_content = reset($group_contents);
      $group = $group_content->getGroup();
    }

    if (UserHelper::isPowerUser($account)) {
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    if (
      $group instanceof GroupInterface &&
      $group->get('moderation_state')->value === DefaultContentModerationStates::ARCHIVED_STATE
    ) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    return AccessResult::allowed()->addCacheableDependency($group);
  }

}
