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
   * created in the past 30 days.
   */
  public function getNumberOfBundleNodesPast30Days($bundle);

  /**
   * Returns number of nodes of content type grouped by terms.
   */
  public function getNodesOfBundlePerTerm($bundle, $taxonomyField, $chartType, $parentTermId);

  /**
   * Returns most viewed nodes of given bundle.
   */
  public function getMostViewedNodesOfBundle($bundle);

}
