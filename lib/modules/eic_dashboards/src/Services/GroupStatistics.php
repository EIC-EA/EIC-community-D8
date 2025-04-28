<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
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
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    DashboardHelperInterface $dashboardHelper,
  ) {
    $this->connection = $connection;
    $this->dashboardHelper = $dashboardHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('eic_dashboards.helper'),
    );
  }

  /**
   * Returns number of groups of given type.
   */
  public function getNumberOfGroups($groupType): array|int {
    $query = $this->connection->select('groups', 'g')
      ->condition('g.type', $groupType)
      ->countQuery();

    return $query->execute()->fetchField();
  }

  /**
   * Returns number of groups of given type
   * created in the past days.
   */
  public function getNumberOfGroupsPastDays($groupType, $days = 30): array|int {
    $query = $this->connection->select('groups', 'g');
    $query->innerJoin('groups_field_data', 'gfd', 'g.id = gfd.id');
    $query->condition('g.type', $groupType)
      ->condition('gfd.created', strtotime('-' . $days . ' days'), '>=');

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
    $query->addExpression('COUNT(ogv.type)', 'groups_count');
    $query->addExpression('ogv.type', 'visibility');
    $query->condition('g.type', $groupType);
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
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'members_count');
    $query->condition('gcfd.type', $membershipType);
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
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'nodes_count');
    $query->condition('gcfd.type', $contentType);

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
    $query->addExpression('gfd.id', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('fc.count', 'flag_count');
    $query->condition('gfd.type', $groupType);
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
          'url' => '/group/' . $result->group_id,
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
    $subquery->join(
      $min_delta_query,
      'mdt',
      'glf.entity_id = mdt.entity_id AND glf.delta = mdt.min_delta'
    );

    // Main query to count groups by location.
    $query = $this->connection->select($subquery, 'sq');
    $query->addExpression('COUNT(DISTINCT sq.entity_id)', 'groups_count');
    $query->addField('sq', 'country_code');
    $query->groupBy('country_code');
    $query->orderBy('country_code', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $row) {
      $data[$row->country_code][$argumentId] = $row->country_code;
      $data[$row->country_code]['label'] = $countries[mb_strtoupper($row->country_code)] ?? '';
      $data[$row->country_code]['count'] = $row->groups_count;
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
   * Returns number of groups with at least one project.
   */
  public function getNumberOfGroupsWithProject($groupType, $projectField): array|int {
    $query = $this->connection->select('group__' . $projectField, 'gpf');
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

}
