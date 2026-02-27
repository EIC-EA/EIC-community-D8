<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group statistics class.
 */
class GroupStatistics implements GroupStatisticsInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardHelperInterface
   */
  protected DashboardHelperInterface $dashboardHelper;

  /**
   * The country service.
   *
   * @var \Drupal\eic_dashboards\Services\CountryServiceInterface
   */
  protected CountryServiceInterface $countryService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    DashboardHelperInterface $dashboardHelper,
    CountryServiceInterface $countryService,
  ) {
    $this->connection = $connection;
    $this->dashboardHelper = $dashboardHelper;
    $this->countryService = $countryService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.country_service'),
    );
  }

  /**
   * Returns number of groups of given type.
   */
  public function getNumberOfGroups($groupType): array|int {
    $query = $this->connection->select('groups', 'g');
    $query->condition('g.type', $groupType);

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns number of groups of given type
   * created in the past days.
   */
  public function getNumberOfGroupsPastDays($groupType, $days = 30): array|int {
    $query = $this->connection->select('groups', 'g');
    $query->innerJoin('groups_field_data', 'gfd', 'g.id = gfd.id');
    $query->condition('g.type', $groupType);
    $query->condition('gfd.created', strtotime('-' . $days . ' days'), '>=');

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns groups grouped by status.
   */
  public function getGroupsByStatus($groupType): array {
    $query = $this->connection->select('content_moderation_state_field_data', 'cmsfd');
    $query->innerJoin('groups', 'g', 'g.id = cmsfd.content_entity_id');
    $query->addExpression('COUNT(cmsfd.moderation_state)', 'groups_count');
    $query->addExpression('cmsfd.moderation_state', 'status');
    $query->condition('cmsfd.content_entity_type_id', 'group');
    $query->condition('g.type', $groupType);
    $query->groupBy('status');
    $query->orderBy('groups_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[] = [
        'name' => ucfirst($result->status),
        'y' => (int) $result->groups_count,
      ];
    }

    return $data;
  }

  /**
   * Returns groups grouped by visibility.
   */
  public function getGroupsByVisibility($groupType): array {
    $query = $this->connection->select('oec_group_visibility', 'ogv');
    $query->innerJoin('groups', 'g', 'g.id = ogv.gid');

    if($groupType == 'group') {
      $query->innerJoin('content_moderation_state_field_data', 'cmsfd', 'g.id = cmsfd.content_entity_id');
    }

    $query->addExpression('COUNT(ogv.type)', 'groups_count');
    $query->addExpression('ogv.type', 'visibility');
    $query->condition('g.type', $groupType);

    if($groupType == 'group') {
      $query->condition('cmsfd.content_entity_type_id', 'group');
      $query->condition('cmsfd.moderation_state', 'published');
    }

    $query->groupBy('visibility');
    $query->orderBy('groups_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[] = [
        'name' => ucfirst(explode("_", $result->visibility)[0]),
        'y' => (int) $result->groups_count,
      ];
    }

    return $data;
  }

  /**
   * Returns top groups by number of members.
   */
  public function getTopGroupsByMembers($membershipType, $range = 10, $days = NULL): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('groups_field_data', 'gfd', 'gfd.id = gcfd.gid');
    $query->innerJoin('users_field_data', 'ufd', 'gcfd.entity_id = ufd.uid');
    $query->innerJoin('content_moderation_state_field_data', 'cmsfd', 'cmsfd.content_entity_id = gcfd.gid');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'members_count');
    $query->condition('gcfd.type', $membershipType);
    $query->condition('cmsfd.content_entity_type_id', 'group');
    $query->condition('cmsfd.moderation_state', 'published');
    $query->condition('ufd.status', 1);

    if ($days) {
      $query->condition('gcfd.created', strtotime('-' . $days . ' days'), '>=');
    }

    $query->groupBy('group_id');
    $query->groupBy('label');
    $query->orderBy('members_count', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[$result->group_id]['id'] = $result->label;
      $data[$result->group_id]['label'] = $result->label;
      $data[$result->group_id]['count'] = $result->members_count;
    }

    return $data;
  }

  /**
   * Returns top groups by number of given content type.
   */
  public function getTopGroupsByContentType($contentType, $range = 10, $days = NULL): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('groups_field_data', 'gfd', 'gfd.id = gcfd.gid');
    $query->innerJoin('content_moderation_state_field_data', 'cmsfd', 'cmsfd.content_entity_id = gcfd.gid');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'nodes_count');
    $query->condition('gcfd.type', $contentType);
    $query->condition('cmsfd.content_entity_type_id', 'group');
    $query->condition('cmsfd.moderation_state', 'published');

    if ($days) {
      $query->condition('gcfd.created', strtotime('-' . $days . ' days'), '>=');
    }

    $query->groupBy('group_id');
    $query->groupBy('label');
    $query->orderBy('nodes_count', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[$result->group_id]['id'] = $result->label;
      $data[$result->group_id]['label'] = $result->label;
      $data[$result->group_id]['count'] = $result->nodes_count;
    }

    return $data;
  }

  /**
   * Returns top groups by number of flag count.
   */
  public function getTopGroupsByFlag($groupType, $flagID, $range = 10, $chartType = 'column'): array {
    $query = $this->connection->select('flag_counts', 'fc');
    $query->innerJoin('groups_field_data', 'gfd', 'gfd.id = fc.entity_id');

    if($groupType == 'group') {
      $query->innerJoin('content_moderation_state_field_data', 'cmsfd', 'cmsfd.content_entity_id = gfd.id');
    }

    $query->addExpression('gfd.id', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('fc.count', 'flag_count');
    $query->condition('gfd.type', $groupType);

    if($groupType == 'group') {
      $query->condition('cmsfd.content_entity_type_id', 'group');
      $query->condition('cmsfd.moderation_state', 'published');
    }

    $query->condition('fc.flag_id', $flagID);
    $query->groupBy('group_id');
    $query->groupBy('label');
    $query->orderBy('flag_count', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    if($chartType == 'column') {
      foreach ($results as $result) {
        $data[$result->group_id]['id'] = $result->label;
        $data[$result->group_id]['label'] = $result->label;
        $data[$result->group_id]['count'] = $result->flag_count;
      }
    } elseif ($chartType == 'list') {
      if ($flagID == 'recommend_group') {
        $flagName = 'likes';
      }
      foreach ($results as $result) {
        $data[] = [
          'prefix' => (int) $result->flag_count . ' ' . $flagName,
          'title' => $result->label,
          'url' => Url::fromUserInput('/group/' . $result->group_id)->toString(),
        ];
      }
    }

    return $data;
  }

  /**
   * Returns number of groups per taxonomy term.
   */
  public function getGroupsByTerm($groupType, $taxonomyField): array {
    $query = $this->connection->select('group__' . $taxonomyField, 'gtf');
    $query->addExpression('COUNT(gtf.' . $taxonomyField . '_target_id)', 'groups_count');
    $query->addExpression('gtf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('gtf.bundle', $groupType);
    $query->groupBy('taxonomy_term_id');
    $query->orderBy('groups_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[] = [
        'name' => $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id) ?? 'NA',
        'y' => (int) $result->groups_count,
      ];
    }

    return $data;
  }

  /**
   * Returns groups grouped by location.
   */
  public function getGroupsGroupedByLocation($groupType, $locationField, $argumentId): array {
    // Get only the first location value for each group.
    $subquery = $this->connection->select('group__' . $locationField, 'glf');
    $subquery->addField('glf', 'entity_id');
    $subquery->addField('glf', $locationField . '_country_code', 'country_code');
    $subquery->condition('glf.bundle', $groupType);

    // Add a subquery join to get only the minimum delta.
    $min_delta_query = $this->connection->select('group__' . $locationField, 'glfj');
    $min_delta_query->fields('glfj', ['entity_id']);
    $min_delta_query->addExpression('MIN(glfj.delta)', 'min_delta');
    $min_delta_query->groupBy('glfj.entity_id');
    $subquery->join($min_delta_query, 'mdq', 'glf.entity_id = mdq.entity_id AND glf.delta = mdq.min_delta');

    // Main query to count groups by location.
    $query = $this->connection->select($subquery, 'sq');
    $query->addExpression('COUNT(DISTINCT sq.entity_id)', 'groups_count');
    $query->addField('sq', 'country_code');
    $query->groupBy('country_code');
    $query->orderBy('country_code', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];

    $countries = $this->countryService->getAllCountries();
    foreach ($results as $result) {
      $data[$result->country_code][$argumentId] = $result->country_code;
      $data[$result->country_code]['label'] = $countries[mb_strtoupper($result->country_code)] ?? '';
      $data[$result->country_code]['count'] = $result->groups_count;
    }

    return $data;
  }

  /**
   * Returns top terms used by groups.
   */
  public function getTopTermsOfGroups($groupType, $taxonomyField, $range = 10) {
    $query = $this->connection->select('group__' . $taxonomyField, 'gtf');
    $query->addExpression('COUNT(gtf.' . $taxonomyField. '_target_id)', 'term_count');
    $query->addExpression('gtf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('gtf.bundle', $groupType);
    $query->groupBy('taxonomy_term_id');
    $query->orderBy('term_count', 'DESC');
    $query->range(0, $range);
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[$result->taxonomy_term_id]['id'] = $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id) ?? 'NA';
      $data[$result->taxonomy_term_id]['label'] = $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id) ?? 'NA';
      $data[$result->taxonomy_term_id]['count'] = $result->term_count;
    }

    return $data;
  }

  /**
   * Returns number of groups with given field populated.
   */
  public function getNumberOfGroupsWithPopulatedField($groupType, $groupField): array|int {
    $query = $this->connection->select('group__' . $groupField, 'gpf');
    $query->addExpression('COUNT(DISTINCT gpf.entity_id)', 'groups_count');
    $query->condition('gpf.bundle', $groupType);

    return $query->execute()->fetchField();
  }

  /**
   * Returns number of groups with at least one member.
   */
  public function getNumberOfGroupsWithMembers($membershipType): array|int {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->addExpression('COUNT(DISTINCT gcfd.gid)', 'groups_count');
    $query->condition('gcfd.type', $membershipType);
    $query->condition('gcfd.label', ['Community Manager'], 'NOT IN');

    return $query->execute()->fetchField();
  }

  /**
   * Returns number of groups per value from a list field.
   */
  public function getGroupsPerValue($groupType, $listField): array|int {
    $query = $this->connection->select('group__' . $listField, 'glf');
    $query->addExpression('COUNT(glf.' . $listField . '_value)', 'groups_count');
    $query->addExpression('glf.' . $listField . '_value', 'value');
    $query->condition('glf.bundle', $groupType);
    $query->groupBy('value');
    $query->orderBy('groups_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];
    $entityTypeId = 'group';

    foreach ($results as $result) {
      $data[] = [
        'name' => $this->dashboardHelper->getListFieldValue($entityTypeId, $listField, $result->value),
        'y' => (int) $result->groups_count,
      ];
    }

    return $data;
  }

  /**
   * Returns number of projects linked from organisations.
   */
  public function getNumberOfProjectsLinkedToOrganisations(): array|int {
    $query = $this->connection->select('group__field_project_grant_agreement_id', 'gfpgai');
    $query->innerJoin('group__field_organisation_project_id', 'gfopi', 'gfopi.field_organisation_project_id_value = gfpgai.field_project_grant_agreement_id_value');
    $query->addExpression('COUNT(DISTINCT gfopi.field_organisation_project_id_value)', 'projects_count');
    $query->condition('gfpgai.bundle', 'project');
    $query->condition('gfopi.bundle', 'organisation');

    return $query->execute()->fetchField();
  }

  /**
   * Returns projects grouped by location of linked organisation.
   */
  public function getProjectsGroupedByLocationOfOrganisation($argumentId): array {
    $query = $this->connection->select('group__field_project_grant_agreement_id', 'gfpgai');
    $query->innerJoin('stakeholder_field_data', 'sfd', 'sfd.project_id = gfpgai.field_project_grant_agreement_id_value');
    $query->innerJoin('stakeholder__field_stakeholder_address', 'sfsa', 'sfsa.entity_id = sfd.id');
    $query->addExpression('COUNT(sfsa.field_stakeholder_address_country_code)', 'projects_count');
    $query->addExpression('sfsa.field_stakeholder_address_country_code', 'country_code');
    $query->condition('sfd.bundle', 'coordinator');
    $query->groupBy('country_code');
    $query->orderBy('country_code', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];

    $countries = $this->countryService->getAllCountries();
    foreach ($results as $result) {
      $data[$result->country_code][$argumentId] = $result->country_code;
      $data[$result->country_code]['label'] = $countries[mb_strtoupper($result->country_code)] ?? '';
      $data[$result->country_code]['count'] = $result->projects_count;
    }

    return $data;
  }

  /**
   * Returns groups of given type created in given period.
   */
  public function getGroupsCreatedInGivenPeriod($groupType, $startDate, $endDate, $range = 10): array {
    if (isset($startDate) && $startDate != "" && isset($endDate) && $endDate != "") {
      // Convert string dates to DateTime objects if necessary.
      if (is_string($startDate)) {
        $startDate = new \DateTime($startDate);
      }
      if (is_string($endDate)) {
        $endDate = new \DateTime($endDate);
      }

      // Ensure dates are at the start/end of their respective days.
      $startDate->setTime(0, 0, 0);
      $endDate->setTime(23, 59, 59);
    }

    $query = $this->connection->select('groups', 'g');
    $query->innerJoin('groups_field_data', 'gfd', 'g.id = gfd.id');
    $query->addExpression('g.id', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(gfd.created), '%d %b %Y')", 'created');
    $query->condition('g.type', $groupType);
    if (isset($startDate) && $startDate != "" && isset($endDate) && $endDate != "") {
      $query->condition('gfd.created', [
        $startDate->getTimestamp(),
        $endDate->getTimestamp(),
      ], 'BETWEEN');
    }
    $query->orderBy('gfd.created', 'DESC');
    if ($range) {
      $query->range(0, $range);
    }

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      return [];
    }

    $data = [];
    foreach ($results as $result) {
      $data[] = [
        'prefix' => $result->created,
        'title' => $result->label,
        'url' => '/group/' . $result->group_id,
      ];
    }

    return $data;
  }

  /**
   * Returns the number of given group members.
   */
  public function getGroupMembers(GroupInterface $group) {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->addExpression('COUNT(DISTINCT entity_id)', 'count');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership');
    $result = $query->execute()->fetchAll();

    return $result[0]->count;
  }

  /**
   * Returns number of group members that joined in the past days.
   */
  public function getGroupMembersRegisteredPastDays(GroupInterface $group, $days = 30) {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->fields('gcfd', ['entity_id']);
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership');
    $query->condition('gcfd.created', strtotime('-' . $days . ' days'), '>=');

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns number of group members that logged in the past days.
   */
  public function getGroupMembersLoggedPastDays(GroupInterface $group, $days = 30): int {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('profile', 'p', 'gcfd.entity_id = p.profile_id');
    $query->innerJoin('users_field_data', 'ufd', 'p.profile_id = ufd.uid');
    $query->fields('gcfd', ['entity_id']);
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership');
    $query->condition('ufd.login', strtotime('-' . $days . ' days'), '>=');

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns group members grouped by country.
   */
  public function getGroupMembersGroupedByCountry(GroupInterface $group, $countryId, $groupId): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('profile', 'p', 'gcfd.entity_id = p.profile_id');
    $query->innerJoin('profile__field_location_address', 'pfla', 'p.profile_id = pfla.entity_id');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('COUNT(pfla.field_location_address_country_code)', 'members_count');
    $query->addExpression('pfla.field_location_address_country_code', 'country_code');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership');
    $query->condition('p.type', 'member');
    $query->groupBy('country_code');
    $query->orderBy('country_code', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];

    $countries = $this->countryService->getAllCountries();
    foreach ($results as $result) {
      $data[$result->country_code][$countryId] = $result->country_code;
      $data[$result->country_code][$groupId] = $result->group_id;
      $data[$result->country_code]['label'] = $countries[mb_strtoupper($result->country_code)] ?? '';
      $data[$result->country_code]['count'] = $result->members_count;
    }

    return $data;
  }

  /**
   * Returns group members grouped by vocabulary.
   */
  public function getGroupMembersPerTaxonomyTerm(GroupInterface $group, $taxonomyField, $argumentId, $groupId): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('profile', 'p', 'gcfd.entity_id = p.profile_id');
    $query->innerJoin('profile__' . $taxonomyField, 'ptf', 'p.profile_id = ptf.entity_id');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('COUNT(ptf.' . $taxonomyField . '_target_id)', 'members_count');
    $query->addExpression('ptf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $group->getGroupType()->id() . '-group_membership');
    $query->condition('p.type', 'member');
    $query->groupBy('taxonomy_term_id');
    $query->orderBy('members_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[$result->taxonomy_term_id][$argumentId] = $result->taxonomy_term_id;
      $data[$result->taxonomy_term_id][$groupId] = $result->group_id;
      $data[$result->taxonomy_term_id]['label'] = $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id);
      $data[$result->taxonomy_term_id]['count'] = (int) $result->members_count;
    }

    return $data;
  }

  /**
   * Returns number of content type's nodes of a given group in a given period.
   */
  public function getNumberOfContentInGivenPeriod(GroupInterface $group, $contentType, $days = NULL) {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $contentType);

    if ($days) {
      $query->condition('gcfd.created', strtotime('-' . $days . ' days'), '>=');
    }

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Returns number of group nodes that belong to group, grouped by taxonomy term.
   */
  public function getGroupNodesOfGroupByTerm($group, $groupNodeType, $taxonomyField): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('node__' . $taxonomyField, 'ntf', 'gcfd.entity_id = ntf.entity_id');
    $query->condition('gcfd.gid', $group->id());
    $query->condition('gcfd.type', $groupNodeType);
    $query->addExpression('COUNT(ntf.' . $taxonomyField . '_target_id)', 'group_nodes_count');
    $query->addExpression('ntf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->groupBy('taxonomy_term_id');
    $query->orderBy('group_nodes_count', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $result) {
      $data[$result->taxonomy_term_id]['id'] = $result->taxonomy_term_id;
      $data[$result->taxonomy_term_id]['label'] = $this->dashboardHelper->getTaxonomyTermLabel($result->taxonomy_term_id);
      $data[$result->taxonomy_term_id]['count'] = (int) $result->group_nodes_count;
    }

    return $data;
  }

}
