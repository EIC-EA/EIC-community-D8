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
   * Returns the number of active platform members registered in the past N days.
   *
   * @param int $days
   *   The number of days to look back from now.
   *
   * @return int
   *   The total number of users registered in the past N days.
   */
  public function getMembersRegisteredPastDays(int $days): int;

  /**
   * Gets the most recently registered active members in a given period.
   *
   * Retrieves the latest registered users with an active status,
   * along with their first name, last name, country name,
   * profile URL, and registration date.
   *
   * @param $startDate
   *   The start date.
   * @param $endDate
   *   The end date.
   * @param int $maxResults
   *   The maximum number of users to retrieve.
   *
   * @return array
   *   An array of members
   */
  public function getMembersListInGivenPeriod($startDate, $endDate, int $maxResults): array;

  /**
   * Returns the number of active users who have logged in during the past N days.
   *
   * @param int $days
   *   The number of days to look back from the current time.
   *
   * @return int
   *   The total number of active users who logged in within the given time period.
   */
  public function getPlatformMembersLoggedPastDays(int $days): int;

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
