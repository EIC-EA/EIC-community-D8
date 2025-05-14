<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;

class DashboardCumulativeService {

  public function __construct(
    protected Connection $connection,
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Insert or update the counter for given dashboard_type and date.
   *
   * @param string $dashboard_type
   * @param string $date
   * @param int|null $count
   *
   * @return void
   * @throws \Exception
   */
  public function insertOrUpdate(string $dashboard_type, string $date, int|null $count = NULL) {
    // Check if entry exists.
    if (!$this->checkEntry($dashboard_type, $date)) {
      // insert entry
//      $this->insert();
      $this->connection->insert(DashboardsDatabase::DASHBOARDS_DATABASE)
        ->fields([
          'dashboard_type' => $dashboard_type,
          'date' => $date,
          'count' => $count ?: 1,
        ])
        ->execute();
    }
    else {
      $entry = $this->getEntry($dashboard_type, $date);
      if (is_null($count)) {
        $count = (int) $entry['count'];
        $count++;
      }
      $entry['count'] = $count;
      $this->merge($entry);
    }
  }

  /**
   * Calculates the cumulative statistics for the given $dashboard_type.
   *
   * The column 'count' must be populated with data of the referencing date.
   * This method adds all previous counts per month and uses the column
   * 'cumulative_count'.
   *
   * @param $dashboard_type
   *
   * @return void
   * @throws \Exception
   */
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
        'cumulative_count' => $results[$i]->count,
      ];
      $this->merge($merge_array);
    }
  }

  /**
   * Checks if an entry exists in the table.
   *
   * @param string $dashboard_type
   * @param string $date
   *
   * @return bool
   * @throws \Exception
   */
  private function checkEntry(string $dashboard_type, string $date) {
    return (bool) $this->getEntry($dashboard_type, $date);
  }

  /**
   * Searches and returns a specific entry in the table.
   *
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

  /**
   * Get the accumulative data from the database per dashboard type.
   *
   * @param $dashboard_type
   *
   * @return array
   * @throws \Exception
   */
  public function getCumulativeStatsPerDashboardType($dashboard_type): array {
    $query = $this->connection->select(DashboardsDatabase::DASHBOARDS_DATABASE, 'dd');
    $query->addExpression('dd.cumulative_count', 'count');
    $query->addExpression('dd.date', 'date');
    $query->condition('dd.dashboard_type', $dashboard_type);
    $query->orderBy('date', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];
    foreach ($results as $key => $row) {
      $data[$key]['id'] = $this->dateFormatter->format(strtotime($row->date), 'custom', 'M Y');
      $data[$key]['count'] = (int) $row->count;
    }

    return $data;
  }

  public function fn($bundle, $startDate, $endDate, $dashboard_type, $range = 10) {
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

    switch ($dashboard_type) {
      case DashboardsDatabase::MEMBERS_DASHBOARD_TYPE:
        break;
      case DashboardsDatabase::GROUPS_DASHBOARD_TYPE:
        break;
      case DashboardsDatabase::EVENTS_DASHBOARD_TYPE:
        break;

      case DashboardsDatabase::ORGANISATIONS_DASHBOARD_TYPE:
        break;
      case DashboardsDatabase::PROJECTS_DASHBOARD_TYPE:
        break;
      case DashboardsDatabase::DOCUMENTS_DASHBOARD_TYPE:

        break;
      case DashboardsDatabase::STORIES_DASHBOARD_TYPE:
        break;
      case DashboardsDatabase::DISCUSSIONS_DASHBOARD_TYPE:
        break;
    }



  }

  private function getStatsNode($bundle, $startDate, $endDate) {
    // Build the query.
    $query = $this->connection->select('node', 'n');
    $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
    $query->addExpression('n.nid', 'node_id');
    $query->addExpression('nfd.title', 'title');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(nfd.created), '%d %b %Y')", 'created');
    $query->condition('n.type', $bundle);
    if (isset($startDate) && $startDate != "" && isset($endDate) && $endDate != "") {
      $query->condition('nfd.created', [
        $startDate->getTimestamp(),
        $endDate->getTimestamp(),
      ], 'BETWEEN');
    }
    $query->orderBy('nfd.created', 'DESC');

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      return [];
    }
  }

  /**
   * @return string[]
   */
  public function getAllDashboardTypes():array {
    return [
      DashboardsDatabase::MEMBERS_DASHBOARD_TYPE,
      DashboardsDatabase::GROUPS_DASHBOARD_TYPE,
      DashboardsDatabase::EVENTS_DASHBOARD_TYPE,
      DashboardsDatabase::ORGANISATIONS_DASHBOARD_TYPE,
      DashboardsDatabase::PROJECTS_DASHBOARD_TYPE,
      DashboardsDatabase::DOCUMENTS_DASHBOARD_TYPE,
      DashboardsDatabase::STORIES_DASHBOARD_TYPE,
      DashboardsDatabase::DISCUSSIONS_DASHBOARD_TYPE,
    ];
  }

}
