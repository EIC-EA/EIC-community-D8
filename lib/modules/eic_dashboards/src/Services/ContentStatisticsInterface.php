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

}
