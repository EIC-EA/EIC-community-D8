<?php

namespace Drupal\eic_flags\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Symfony\Component\Routing\Route;

/**
 * Class RequestAccessCheck.
 *
 * @package Drupal\eic_flags\Access
 */
class RequestAccessCheck implements AccessInterface {

  /**
   * The collect for request type handlers.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $collector;

  /**
   * RequestAccessCheck constructor.
   *
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The collect for request type handlers.
   */
  public function __construct(RequestHandlerCollector $collector) {
    $this->collector = $collector;
  }

  /**
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The object representing the result of routing.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *    The access result.
   */
  public function access(
    Route $route,
    RouteMatchInterface $route_match,
    AccountInterface $account
  ) {
    $type = $route_match->getParameter('request_type');
    $entity_type_id = $route->getOption('entity_type_id');
    $entity = $route_match->getParameter($entity_type_id);
    $handler = $this->collector->getHandlerByType($type);

    return $handler->canRequest($account, $entity);
  }

}
