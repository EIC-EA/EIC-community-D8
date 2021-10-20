<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\flag\Access\FlagAccessCheck as FlagAccessCheckBase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Extends FlagAccessCheck class providing extra logic for group flags.
 *
 * @ingroup flag_access
 */
class FlagAccessCheck extends FlagAccessCheckBase {

  /**
   * The flag access check inner service.
   *
   * @var \Drupal\flag\Access\FlagAccessCheck
   */
  protected $flagAccessCheck;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\Access\FlagAccessCheck $flag_access_check_inner_service
   *   The flag access check inner service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(FlagAccessCheckBase $flag_access_check_inner_service, FlagServiceInterface $flag_service) {
    parent::__construct($flag_service);
    $this->flagAccessCheck = $flag_access_check_inner_service;
  }

  /**
   * Checks access to the 'flag' action.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An AccessResult object.
   */
  public function access(RouteMatchInterface $route_match, FlagInterface $flag, AccountInterface $account) {
    $access = $this->flagAccessCheck->access($route_match, $flag, $account);

    // If access is not allowed, we do nothing.
    if (!$access->isAllowed()) {
      return $access;
    }

    $flaggable_id = $route_match->getParameter('entity_id');
    $flaggable_entity = $this->flagService->getFlaggableById($flag, $flaggable_id);

    switch ($flag->getFlaggableEntityTypeId()) {
      case 'group':
        // Deny access to flag if the group IS in pending or draft state.
        if (!EICGroupsHelper::groupIsFlaggable($flaggable_entity)) {
          $access = AccessResult::forbidden()
            ->addCacheableDependency($flaggable_entity)
            ->addCacheableDependency($flag);
        }
        break;

      case 'node':
        // Get the group content entities related to the node.
        $group_contents = GroupContent::loadByEntity($flaggable_entity);

        // Node does not belong to any group, so we do nothing.
        if (empty($group_contents)) {
          break;
        }

        // Load the first group content entity found.
        $group_content = reset($group_contents);

        // Load the group.
        $group = $group_content->getGroup();

        // Deny access to flag if the group IS in pending or draft state.
        if (!EICGroupsHelper::groupIsFlaggable($group)) {
          $access = AccessResult::forbidden()
            ->addCacheableDependency($group)
            ->addCacheableDependency($flaggable_entity)
            ->addCacheableDependency($flag);
        }
        break;

    }

    return $access;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->flagAccessCheck, $method], $args);
  }

}
