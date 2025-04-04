<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines platform statistics interface.
 */
interface PlatformStatisticsInterface {

  /**
   * Returns total number of platform members.
   */
  public function getTotalPlatformMembers();

  /**
   * Returns members grouped by country.
   */
  public function getMembersGroupedByCountry($argumentId);

  /**
   * Returns members grouped by vocabulary.
   */
  public function getMembersPerTaxonomyTerm($taxonomyField, $argumentId);

}
