<?php

namespace Drupal\eic_flags\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Drupal\eic_flags\BlockFlagTypes;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a class to create block_entity routes.
 *
 * @package Drupal\eic_flags\Routing
 */
class EntityBlockRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    $supported_types = array_keys(BlockFlagTypes::getSupportedEntityTypes());
    // Do nothing if entity type is not supported by the handler.
    if (!in_array($entity_type->id(), $supported_types)) {
      return $collection;
    }

    $entity_link_template = 'block-entity';

    // If entity type doesn't have "block-entity" link template, we do nothing.
    if (!$entity_type->hasLinkTemplate($entity_link_template)) {
      return $collection;
    }

    // Defines a block entity route for the entity type.
    $route = (new Route($entity_type->getLinkTemplate($entity_link_template)))
      ->addDefaults([
        '_entity_form' => $entity_type->id() . '.' . $entity_link_template,
        '_title' => 'Block entity',
      ])
      ->setRequirement($entity_type->id(), '\d+')
      ->setRequirement('_access', 'TRUE')
      ->setOption('entity_type_id', $entity_type->id());

    $collection->add(
      'entity.' . $entity_type->id() . '.block_entity',
      $route
    );

    return $collection;
  }

}
