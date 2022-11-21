<?php

namespace Drupal\eic_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter content related routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('node.add_page')) {
      // Add a requirement for /node/add page. Only power users should have
      // access to this page.
      $route->setRequirement('_permission', 'administer nodes');
    }
  }

}
