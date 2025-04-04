<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines dashboards helper interface.
 */
interface DashboardHelperInterface {

  /**
   * Gets title from a routing.
   */
  public function getRoutingTitle($route_name);

  /**
   * Gets URL from a routing.
   */
  public function getRoutingUrl($route_name);

  /**
   * Prepares data for jump menu, links to View with one argument.
   */
  public function prepareDataForJumpMenu($items, $viewMachineName, $routeParameters, $argumentIds);

}
