<?php

namespace Drupal\eic_dashboards\Services;

/**
 * Defines members statistics interface.
 */
interface MembersStatisticsInterface {

  /**
   * Returns total number of platform members.
   */
  public function getTotalMembers();

  /**
   * Returns members grouped by country.
   */
  public function getMembersGroupedByCountry($argumentId);

  /**
   * Returns members grouped by vocabulary.
   */
  public function getMembersPerTaxonomyTerm($taxonomyField, $argumentId);

}
