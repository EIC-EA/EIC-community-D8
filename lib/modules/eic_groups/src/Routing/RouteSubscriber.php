<?php

namespace Drupal\eic_groups\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\eic_groups\ForbiddenOrphanContentTypes;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter some entity add routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    foreach (ForbiddenOrphanContentTypes::FORBIDDEN_ENTITIES as $route_name => $definition) {
      $add_route = $collection->get($route_name);
      if (!$add_route instanceof Route) {
        continue;
      }

      $add_route->setRequirement('_orphan_group_content_access_check', 'TRUE');
    }
  }

}
