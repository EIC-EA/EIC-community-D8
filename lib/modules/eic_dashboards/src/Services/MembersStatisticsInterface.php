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
   * Retrieves the latest active registered members.
   *
   * @param int $maxResults
   *   The maximum number of users to return.
   *
   * @return array
   *   An array of members. Each member array contains:
   *   - first_name: The user's first name (string).
   *   - last_name: The user's last name (string).
   *   - profile_link: A URL string linking to the user's profile page.
   */
  public function getLastRegisteredMembersList(int $maxResults): array;

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
