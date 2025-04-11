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

}
