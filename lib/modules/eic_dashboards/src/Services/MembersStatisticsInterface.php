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
   * Gets the most recently registered active members.
   *
   * Retrieves the latest registered users with an active status,
   * along with their first name, last name, country name,
   * profile URL, and registration date.
   *
   * @param int $maxResults
   *   The maximum number of users to retrieve.
   *
   * @return array
   *   An array of members
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

  /**
   * Retrieves profile information for all active users.
   *
   * Fetches the user ID, profile ID, topic expertise term ID,
   * topic interest term ID, and country code for each active user.
   *
   * @return int
   *   The total number of users with completed profile.
   */
  public function getTotalCompletedMembersProfiles(): int;
}
