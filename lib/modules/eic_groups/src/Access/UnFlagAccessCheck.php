<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\flag\Access\UnFlagAccessCheck as UnFlagAccessCheckBase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;

/**
 * Extends UnFlagAccessCheck class providing extra logic for group flags.
 *
 * @ingroup flag_access
 */
class UnFlagAccessCheck extends UnFlagAccessCheckBase {

  /**
   * The unflag access check inner service.
   *
   * @var \Drupal\flag\Access\UnFlagAccessCheck
   */
  protected $unFlagAccessCheck;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\Access\UnFlagAccessCheck $unflag_access_check_inner_service
   *   The flag access check inner service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   */
  public function __construct(UnFlagAccessCheckBase $unflag_access_check_inner_service, FlagServiceInterface $flag_service) {
    parent::__construct($flag_service);
    $this->unFlagAccessCheck = $unflag_access_check_inner_service;
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
    $access = $this->unFlagAccessCheck->access($route_match, $flag, $account);

    if ($access->isAllowed()) {

      // If the flaggable entity is not a group, we do nothing.
      if ($flag->getFlaggableEntityTypeId() !== 'group') {
        return $access;
      }

      $flaggable_id = $route_match->getParameter('entity_id');
      $flaggable_entity = $this->flagService->getFlaggableById($flag, $flaggable_id);

      $moderation_state = $flaggable_entity->get('moderation_state')->value;

      // Deny access to flag if the group is NOT in pending or draft state.
      if (in_array($moderation_state, [
        GroupsModerationHelper::GROUP_PENDING_STATE,
        GroupsModerationHelper::GROUP_DRAFT_STATE,
      ])) {
        $access = AccessResult::forbidden()
          ->addCacheableDependency($flaggable_entity)
          ->addCacheableDependency($flag);
      }
    }

    return $access;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->unFlagAccessCheck, $method], $args);
  }

}
