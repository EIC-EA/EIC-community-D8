<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Community statistics class.
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
   * @var \Drupal\eic_dashboards\Services\DashboardHelperinterface
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
  public function getTopGroupsByMembers($membershipType, $range = 10): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('groups_field_data', 'gfd', 'gfd.id = gcfd.gid');
    $query->innerJoin('users_field_data', 'ufd', 'gcfd.entity_id = ufd.uid');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'members_count');
    $query->condition('gcfd.type', $membershipType);
    $query->condition('ufd.status', 1);
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
  public function getTopGroupsByContentType($contentType, $range = 10): array {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->innerJoin('groups_field_data', 'gfd', 'gfd.id = gcfd.gid');
    $query->addExpression('gcfd.gid', 'group_id');
    $query->addExpression('gfd.label', 'label');
    $query->addExpression('COUNT(gcfd.gid)', 'nodes_count');
    $query->condition('gcfd.type', $contentType);
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

}
