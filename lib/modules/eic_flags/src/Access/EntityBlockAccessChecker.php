<?php

namespace Drupal\eic_flags\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\Service\EntityBlockHandler;
use Symfony\Component\Routing\Route;

/**
 * Checks access to block an entity.
 *
 * To make use of this access checker add
 * '_entity_block_access_checker: Some value' entry to route definition under
 * requirements section.
 */
class EntityBlockAccessChecker implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC Flags entity block handler service.
   *
   * @var \Drupal\eic_flags\Service\EntityBlockHandler
   */
  protected $entityBlockHandler;

  /**
   * EntityBlockAccessChecker constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_flags\Service\EntityBlockHandler $entity_block_handler
   *   The EIC Flags entity block handler service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityBlockHandler $entity_block_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBlockHandler = $entity_block_handler;
  }

  /**
   * Checks routing access for the blocked group route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The object representing the result of routing.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(
    Route $route,
    RouteMatchInterface $route_match,
    AccountInterface $account
  ) {
    // Default access.
    $access = AccessResult::forbidden();

    $entity_type = $route->getOption('entity_type_id');

    $entity = $route_match->getParameter($entity_type);

    if (!$entity instanceof ContentEntityInterface) {
      if (!is_numeric($entity)) {
        return $access;
      }

      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity);
    }

    return $this->entityBlockHandler->canBlockEntity($entity, $account);
  }

}
