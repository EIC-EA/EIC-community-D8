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

  /**
   * Counts distinct active users linked via a specific group membership type.
   *
   * @param string $membershipType
   *   The group content type (e.g. 'organisation-group_membership' or 'project-group_membership').
   *
   * @return int
   *   The number of distinct active users with that membership type.
   */
  public function getMembersLinkedByType(string $membershipType): int;
}
