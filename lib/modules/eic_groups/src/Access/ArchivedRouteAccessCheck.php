<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_user\UserHelper;

/**
 * Checks access for archived groups.
 *
 * @package Drupal\eic_groups\Access
 */
class ArchivedRouteAccessCheck implements AccessInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The current route.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRoute;

  /**
   * The route match service.
   *
   * @var Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $groupsHelper;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $groups_helper
   *   The EIC groups helper service.
   */
  public function __construct(RouteMatchInterface $route_match, EICGroupsHelperInterface $groups_helper) {
    $this->currentRoute = $route_match;
    $this->groupsHelper = $groups_helper;
  }

  /**
   * Performs the access check.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(RouteMatchInterface $route_match, AccountProxy $account) {
    $group = $route_match->getParameter('group');
    $group_content = $route_match->getParameter('group_content');
    $node = $route_match->getParameter('node');

    $is_archived_entity =
      $this->groupsHelper->isGroupArchived($group) ||
      $this->groupsHelper->isGroupArchived($group_content) ||
      $this->groupsHelper->isGroupArchived($node);

    if (UserHelper::isPowerUser($account)) {
      // @todo EICNET-2967: filter the sensitive groups.
      return AccessResult::allowed()->addCacheableDependency($group);
    }

    if ($is_archived_entity) {
      // Try to find out the group.
      if (is_null($group)) {
        if (!is_null($node)) {
          $group = $this->groupsHelper->getOwnerGroupByEntity($node);
        }
        elseif (!is_null($group_content)) {
          $group = $this->groupsHelper->getOwnerGroupByEntity($group_content);
        }
      }

      if (!is_null($group)) {
        // Check if the given route is the current route.
        if ($route_match->getRouteName() == $this->currentRoute->getRouteName()) {
          // Print a message to the user to explain they cannot perform this
          // action.
          $message = $this->t('This @group_type is now archived. You cannot perform this action.', [
            '@group_type' => strtolower($group->getGroupType()->label()),
          ]);
          $this->messenger()->addWarning($message);
        }
      }

      return AccessResult::forbidden()->addCacheableDependency($group);
    }

    return AccessResult::allowed()->addCacheableDependency($group);
  }

}
