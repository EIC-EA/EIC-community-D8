<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines content statistics interface.
 */
interface ContentStatisticsInterface {

  /**
   * Returns number of content of given bundle.
   */
  public function getNumberOfBundleNodes($bundle);

  /**
   * Returns number of content of given bundle
   * created in the past days.
   */
  public function getNumberOfBundleNodesPastDays($bundle, $days);

  /**
   * Returns number of nodes of content type grouped by terms.
   */
  public function getNodesOfBundlePerTerm($bundle, $taxonomyField, $chartType, $parentTermId, $range);

  /**
   * Returns information about last story nodes.
   */
  public function getLastStoriesMetrics($range);

  /**
   * Returns most viewed nodes of given bundle.
   */
  public function getMostViewedNodesOfBundle($bundle, $range);

  /**
   * Returns most downloaded files of given bundle.
   */
  public function getMostDownloadedFilesOfBundle($bundle, $range);

  /**
   * Returns number of nodes of given bundle grouped by value from a list field.
   */
  public function getNodesOfBundlePerValue($bundle, $listField, $chartType, $range);

  /**
   * Returns most commented nodes of given bundle.
   */
  public function getMostCommentedNodesOfBundle($bundle, $range);


  /**
   * Returns list of groups based on number of bundle.
   */
  public function getGroupsByNumberOfBundle($bundle, $groupType, $range);

  /**
   * Returns content of bundle created between given dates.
   */
  public function getContentOfBundleInGivenPeriod($bundle, $startDate, $endDate, $range );
}
