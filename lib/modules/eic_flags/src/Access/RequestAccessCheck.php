<?php

namespace Drupal\eic_flags\Access;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RequestAccessCheck constructor.
   *
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The collect for request type handlers.
   */
  public function __construct(
    RequestHandlerCollector $collector
  ) {
    $this->collector = $collector;
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   */
  public function setEntityTypeManager(
    ?EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

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

    return $handler->canRequest($account, $entity);
  }

}
