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
   * Transforms data array into categories and series format.
   */
  public function transformNameYToCategoriesSeries($data);

  /**
   * Formats member counts for chart display by collapsing sub-terms.
   *
   * This function:
   * - Accepts raw counts keyed by taxonomy term ID.
   * - Uses getNestedTidTree() to define 2nd-level parent groups.
   * - Builds a reverse lookup to map any term to its top-level group.
   * - Loops over raw data once and updates counts efficiently.
   *
   * @param array $rawData
   *   Flat list of counts keyed by term ID
   *
   * @param string $vid
   *   The vocabulary machine name (e.g. 'topics').
   *
   * @return array
   *   Flattened chart-friendly array.
   */
  public function transformTermTreeCountsForChart(array $rawData, string $vid);

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

  /**
   * Returns the human-readable value of a list field in an entity.
   */
  public function getListFieldValue(string $entityTypeId, string $fieldName, string $listItemValue);
}
