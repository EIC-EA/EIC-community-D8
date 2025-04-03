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

}
