<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides HTML routes for overview page entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider.
 */
class OverviewPageHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    $route->setRequirement('_permission', 'access overview page overview');
    return $route;
  }

}
