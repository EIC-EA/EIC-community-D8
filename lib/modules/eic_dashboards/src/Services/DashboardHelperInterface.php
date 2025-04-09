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

  /**
   * Encodes given data to JSON format for chart use.
   */
  public function jsonEncodeCategoriesSeries($data);

  /**
   * Transforms data array into categories and series format.
   */
  public function transformIdCountToCategoriesSeries($data);

  /**
   * Flat tree of taxonomy terms with collapsed depth info.
   *
   * @param string $vid
   *    The machine name (ID) of the taxonomy vocabulary to load.
   */
  public function loadTreeCollapsedToSecondLevel(string $vid);

  /**
   * Gets the taxonomy term label.
   *
   * @param string $tid
   *   Taxonomy id.
   *
   * @return array
   *   The label of the term or NA if not found.
   */
  public function getTaxonomyTermLabel(string $tid);
}
