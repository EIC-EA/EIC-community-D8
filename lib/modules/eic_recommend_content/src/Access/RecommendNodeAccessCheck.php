<?php

namespace Drupal\eic_recommend_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_user\UserHelper;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * To make use of this access checker add
 * '_eic_recommend_content_node: Some value' entry to route definition under
 * requirements section.
 */
class RecommendNodeAccessCheck implements AccessInterface {

  /**
   * Checks routing access for the recommend node route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node
   *   (optional) A node object. If the $node is not specified, then access is
   *   denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, NodeInterface $node = NULL) {
    if (!$node) {
      return AccessResult::forbidden();
    }

    // Default access.
    $access = AccessResult::forbidden()
      ->addCacheableDependency($account)
      ->addCacheableDependency($node);

    $moderation_state = $node->get('moderation_state')->value;

    switch ($moderation_state) {
      case DefaultContentModerationStates::PUBLISHED_STATE:
        // Anonymous users cannot recommend groups.
        if ($account->isAnonymous()) {
          return $access;
        }

        // Power users can always recommend published groups.
        if (UserHelper::isPowerUser($account)) {
          $access = AccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($node);
          break;
        }

        // At this point, it means the user is not a power user. If the current
        // user does not have access to view the node, we deny access to
        // recommend it.
        if (!$node->access('view', $account)) {
          break;
        }

        $access = AccessResult::allowed()
          ->addCacheableDependency($account)
          ->addCacheableDependency($node);
        break;

    }

    return $access;
  }

}
