<?php

namespace Drupal\oec_group_flex\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * OEC Group Flex route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($group_membership_route = $collection->get('entity.group.group_request_membership')) {
      $group_membership_route->setRequirement('_group_membership_request_access_check', 'TRUE');
    }
  }

}
