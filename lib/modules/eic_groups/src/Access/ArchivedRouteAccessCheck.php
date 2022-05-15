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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(RouteMatchInterface $route_match, AccountProxy $account) {
    $group = $route_match->getParameter('group');
    $node = $route_match->getParameter('node');
    /** @var \Drupal\eic_groups\EICGroupsHelper $group_helper */
    $group_helper = \Drupal::service('eic_groups.helper');

    $is_archived_entity =
      $group_helper->isGroupArchived($group) ||
      $group_helper->isGroupArchived($node);

    if (UserHelper::isPowerUser($account)) {
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    if ($is_archived_entity) {
      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    return AccessResult::allowed()->addCacheableDependency($group);
  }

}
