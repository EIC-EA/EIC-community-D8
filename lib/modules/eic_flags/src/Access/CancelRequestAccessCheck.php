<?php

namespace Drupal\eic_flags\Access;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Class RequestAccessCheck.
 *
 * @package Drupal\eic_flags\Access
 */
class CancelRequestAccessCheck extends RequestAccessCheck {

  /**
   * Checks if the entity is supported and the user can do a request.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The object representing the result of routing.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(
    Route $route,
    RouteMatchInterface $route_match,
    AccountInterface $account
  ) {
    $type = $route_match->getParameter('request_type');
    $entity_type_id = $route->getOption('entity_type_id');
    $entity = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->load($route_match->getRawParameter($entity_type_id));
    if (!$entity instanceof ContentEntityInterface) {
      throw new \InvalidArgumentException();
    }

    $handler = $this->collector->getHandlerByType($type);

    return $handler->canCancelRequest($account, $entity);
  }

}
