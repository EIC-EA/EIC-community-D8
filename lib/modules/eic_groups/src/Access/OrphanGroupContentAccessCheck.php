<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\eic_groups\ForbiddenOrphanContentTypes;

/**
 * Class OrphanGroupContentAccessCheck
 *
 * @package Drupal\eic_groups\Access
 */
class OrphanGroupContentAccessCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountProxy $account
   *
   * @return \Drupal\Core\Access\AccessResultNeutral
   */
  public function access(RouteMatchInterface $route_match, AccountProxy $account) {
    $route_name = $route_match->getRouteName();
    if (!array_key_exists(
      $route_name,
      ForbiddenOrphanContentTypes::FORBIDDEN_ENTITIES
    )) {
      // Entity type/route is not supported
      return AccessResult::neutral();
    }

    // Check for each defined route
    if ('node.add' === $route_name) {
      /** @var \Drupal\node\Entity\NodeType $node_type */
      $node_type = $route_match->getParameter('node_type');

      return AccessResult::allowedIf(
        !in_array($node_type->id(), ForbiddenOrphanContentTypes::FORBIDDEN_ENTITIES)
        || $account->hasPermission('bypass node access'))
        ->addCacheContexts(['url.path']);
    }

    // No idea then.
    return AccessResult::neutral();
  }

}
