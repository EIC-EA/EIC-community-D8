<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\group\Entity\GroupInterface;

/**
 * Defines community statistics interface.
 */
interface GroupStatisticsInterface {
  /**
   * Returns number of groups of given type.
   */
  public function getNumberOfGroups($groupType);

  /**
   * Returns number of groups of given type
   * created in the past days.
   */
  public function getNumberOfGroupsPastDays($groupType, $days);

  /**
   * Returns groups grouped by status.
   */
  public function getGroupsByStatus($groupType);

  /**
   * Returns groups grouped by visibility.
   */
  public function getGroupsByVisibility($groupType);

  /**
   * Returns top groups by number of members.
   */
  public function getTopGroupsByMembers($membershipType, $range, $days);

  /**
   * Returns top groups by number of given content type.
   */
  public function getTopGroupsByContentType($contentType, $range, $days);

  /**
   * Returns top groups by number of flag count.
   */
  public function getTopGroupsByFlag($groupType, $flagID, $range, $chartType);

  /**
   * Returns number of groups per taxonomy term.
   */
  public function getGroupsByTerm($groupType, $taxonomyField);

  /**
   * Returns groups grouped by location.
   */
  public function getGroupsGroupedByLocation($groupType, $locationField, $argumentId);

  /**
   * Returns top terms used by groups.
   */
  public function getTopTermsOfGroups($groupType, $taxonomyField, $range);

  /**
   * Returns number of groups with given field populated.
   */
  public function getNumberOfGroupsWithPopulatedField($groupType, $groupField);

  /**
   * Returns number of groups with at least one member.
   */
  public function getNumberOfGroupsWithMembers($membershipType);

  /**
   * Returns number of groups per value from a list field.
   */
  public function getGroupsPerValue($groupType, $listField);

  /**
   * Returns number of projects linked from organisations.
   */
  public function getNumberOfProjectsLinkedFromOrganisations();

  /**
   * Returns projects grouped by location of linked organisation.
   */
  public function getProjectsGroupedByLocationOfOrganisation($argumentId);

  /**
   * Returns groups of given type created in given period.
   */
  public function getGroupsCreatedInGivenPeriod($groupType, $startDate, $endDate, $range);

  /**
   * Returns the number of given group members.
   */
  public function getGroupMembers(GroupInterface $group);

  /**
   * Returns number of group members that joined in the past days.
   */
  public function getGroupMembersRegisteredPastDays(GroupInterface $group, $days);

  /**
   * Returns number of group members that logged in the past days.
   */
  public function getGroupMembersLoggedPastDays(GroupInterface $group, $days);

  /**
   * Returns group members grouped by country.
   */
  public function getGroupMembersGroupedByCountry(GroupInterface $group, $countryId, $groupId);

  /**
   * Returns group members grouped by vocabulary.
   */
  public function getGroupMembersPerTaxonomyTerm(GroupInterface $group, $taxonomyField, $argumentId, $groupId);

  /**
   * Returns number of content type's nodes of a given group in a given period.
   */
  public function getNumberOfContentInGivenPeriod(GroupInterface $group, $contentType, $days);

  /**
   * Returns number of group nodes that belong to group, grouped by taxonomy term.
   */
  public function getGroupNodesOfGroupByTerm($group, $groupNodeType, $taxonomyField);
}
