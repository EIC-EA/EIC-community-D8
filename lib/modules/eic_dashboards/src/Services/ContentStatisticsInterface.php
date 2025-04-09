<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines platform statistics interface.
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
  public function getNodesOfBundlePerTerm($bundle, $taxonomyField, $chartType, $parentTermId);

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
   * Returns latest nodes of given bundle
   */
  public function getLatestNodesOfBundle($bundle, $range);

}
