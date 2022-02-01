<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\Access\RequestAccessCheck;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Extends RequestAccessCheck class providing extra logic for group flags.
 *
 * @package Drupal\eic_groups\Access
 */
class FlagRequestAccessCheck extends RequestAccessCheck {

  /**
   * The request access check inner service.
   *
   * @var \Drupal\eic_flags\Access\RequestAccessCheck
   */
  protected $requestAccessCheck;

  /**
   * FlagRequestAccessCheck constructor.
   *
   * @param \Drupal\eic_flags\Access\RequestAccessCheck $request_access_check
   *   The request access check inner service.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The request handler collector service.
   */
  public function __construct(RequestAccessCheck $request_access_check, RequestHandlerCollector $collector) {
    parent::__construct($collector);
    $this->requestAccessCheck = $request_access_check;
  }

  /**
   * Checks if user can access the flag action.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An AccessResult object.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $access = $this->requestAccessCheck->access($route, $route_match, $account);

    // If access is not allowed, we do nothing.
    if (!$access->isAllowed()) {
      return $access;
    }

    $entity_type_id = $route->getOption('entity_type_id');
    $entity = $route_match->getParameter($entity_type_id);

    // If the requested entity is not a group, we do nothing.
    if (!($entity instanceof GroupInterface)) {
      return $access;
    }

    $moderation_state = $entity->get('moderation_state')->value;

    // Deny access to flag if the group IS in pending state.
    if (in_array($moderation_state, [
      GroupsModerationHelper::GROUP_PENDING_STATE,
    ])) {
      $access = AccessResult::forbidden()
        ->addCacheableDependency($entity);
    }

    return $access;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->requestAccessCheck, $method], $args);
  }

}
