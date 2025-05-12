<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;

class DashboardCumulativeService {

  public function __construct(protected Connection $connection) {

  }

  public function insertOrUpdate(string $dashboard_type, string $date) {
    // Check if entry exists.
    if (!$this->checkEntry($dashboard_type, $date)) {
      // insert entry
//      $this->insert();
      $this->connection->insert(DashboardsDatabase::DASHBOARDS_DATABASE)
        ->fields([
          'dashboard_type' => $dashboard_type,
          'date' => $date,
          'count' => 1,
        ])
        ->execute();
    }
    else {
      $entry = $this->getEntry($dashboard_type, $date);
      $count = (int) $entry['count'];
      $count++;
      $entry['count'] = $count;
      $this->merge($entry);
    }
  }

  public function calculatePastStats($dashboard_type) {
    $query = $this->connection->select(DashboardsDatabase::DASHBOARDS_DATABASE, 'dd');
    $query->addExpression('dd.count', 'count');
    $query->addExpression('dd.date', 'date');
    $query->condition('dd.dashboard_type', $dashboard_type);
    $query->orderBy('date', 'ASC');
    $results = $query->execute()->fetchAll();

    for ($i = 1; $i < count($results); $i++) {
      $previous_record = $results[$i - 1];
      $previous_count = $previous_record->count;

      $results[$i]->count = $previous_count + $results[$i]->count;
      $merge_array = [
        'dashboard_type' => $dashboard_type,
        'date' => $results[$i]->date,
        'cumulative_count' => $results[$i]->count
      ];
      $this->merge($merge_array);
    }
  }

  private function checkEntry(string $dashboard_type, string $date) {
    return (bool) $this->getEntry($dashboard_type, $date);
  }

  /**
   * @param string $dashboard_type
   * @param string $date
   *
   * @return array{
   *   dashboard_type: string,
   *   date: string,
   *   count: string,
   * }|bool
   * @throws \Exception
   */
  private function getEntry(string $dashboard_type, string $date) {
    $query = $this->connection->select(DashboardsDatabase::DASHBOARDS_DATABASE, 'd')
      ->fields('d', ['dashboard_type', 'date', 'count'])
      ->condition('d.dashboard_type', $dashboard_type)
      ->condition('d.date', $date);
    return $query->execute()->fetchAssoc();
  }

  /**
   * Merge an entry in the database.
   */
  private function merge(array $entry): int {
    try {
      $numberOfRows = $this->connection->merge(DashboardsDatabase::DASHBOARDS_DATABASE)
        ->keys([
          'dashboard_type' => $entry['dashboard_type'],
          'date' => $entry['date'],
        ])
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      echo 'Merge failed - Message: ' . $e->getMessage();
    }
    return $numberOfRows ?? 0;
  }

  private function insert(string $dashboard_type, string $date) {
    // first we have to get the count of the previous
    $this->connection->insert(DashboardsDatabase::DASHBOARDS_DATABASE)
      ->fields([
        'dashboard_type' => $dashboard_type,
        'date' => $date,
        'count' => 1,
      ])
      ->execute();
  }

}
