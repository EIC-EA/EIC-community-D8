<?php

namespace Drupal\eic_dashboards\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;
use Drupal\eic_dashboards\Services\DashboardCumulativeService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class GenerateCumulativeStatsMonthDrushCommands extends DrushCommands {

  /**
   * Constructs a GenerateCumulativeStatsMonthDrushCommands object.
   */
  public function __construct(
    private readonly DashboardCumulativeService $dashboardCumulativeService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.cumulative'),
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * Populate the data of the previous month into the database.
   */
  #[CLI\Command(name: 'eic_dashboards:generate-all-previous-month', aliases: ['dashboard-previous-month'])]
  public function generateAllStatsPreviousMonth() {

    // Set start date to first day of the previous month.
    $startDate = new \DateTime('first day of last month');

    // Set end date to the last day of the previous month.
    $endDate = new \DateTime('last day of last month');

    // Ensure dates are at the start/end of their respective days.
    $startDate->setTime(0, 0, 0);
    $endDate->setTime(23, 59, 59);

    $date = $startDate->format('Y-m-d');

    foreach ($this->dashboardCumulativeService->getAllDashboardTypes() as $dashboardType) {
      $count = $this->dashboardCumulativeService->getCountDashboardTypeInGivenPeriod($startDate, $endDate, $dashboardType);
      if ($count) {
        $this->dashboardCumulativeService->insertOrUpdate($dashboardType, $date, $count);
        $this->dashboardCumulativeService->calculateCumulativeCountMonth($dashboardType, $startDate, $endDate);
        $this->logger()->success(t("Generated data for {$startDate->format('Y-m')} for dashboard type '$dashboardType'"));
      }
      else {
        $this->logger()->error(t("Could not generate data for {$startDate->format('Y-m')} for dashboard type '$dashboardType'"));
      }
    }

  }

  /**
   * Populate the data of the previous month into the database.
   */
  #[CLI\Command(name: 'eic_dashboards:generate-stats', aliases: ['dashboard-stats'])]
  #[CLI\Option(name: 'dashboard-type', description: 'Select to generate stats for a specific dashboard type')]
  #[CLI\Option(name: 'all-stats', description: 'Select if generate all stats. If added, it will ignore dashboard-type option.')]
  #[CLI\Usage(name: 'drush eic_dashboards:generate-stats --dashboard-type=members', description: 'Regenerate all counts for members dashboard type (user entities).')]
  #[CLI\Usage(name: 'drush eic_dashboards:generate-stats --all-stats', description: 'Regenerate all counts for all dashboard types.')]
  public function regenerateStats($options = ['dashboard-type' => null, 'all-stats' => null]) {
    $this->io()->warning("This action is destructive. Data will be calculated differently.");
    $confirm = $this->confirm("Are you sure you want to proceed?");
    if ($confirm) {


      if ($options['all-stats']) {
        $dashboard_type_to_generate = $this->dashboardCumulativeService->getAllDashboardTypes();
      }
      else {
        if (in_array($options['dashboard-type'], $this->dashboardCumulativeService->getAllDashboardTypes())) {
          $dashboard_type_to_generate = [$options['dashboard-type']];
        }
        else {
          $this->logger()
            ->error(t("The selected dashboard type '@type' does not exist.", ['@type' => $options['dashboard-type']]));
          return self::EXIT_FAILURE_WITH_CLARITY;
        }
      }

      foreach ($dashboard_type_to_generate as $dashboard_type) {
        $this->logger()->info("Updating data on $dashboard_type dashboard.");
        switch ($dashboard_type) {
          case DashboardsDatabase::MEMBERS_DASHBOARD_TYPE:
            $entity_type_id = 'user';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('status', '1')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::GROUPS_DASHBOARD_TYPE:
            $entity_type_id = 'group';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'group')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::EVENTS_DASHBOARD_TYPE:
            $entity_type_id = 'group';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'event')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::ORGANISATIONS_DASHBOARD_TYPE:
            $entity_type_id = 'group';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'organisation')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::PROJECTS_DASHBOARD_TYPE:
            $entity_type_id = 'group';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'project')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::DOCUMENTS_DASHBOARD_TYPE:
            $entity_type_id = 'node';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'document')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::STORIES_DASHBOARD_TYPE:
            $entity_type_id = 'node';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'story')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;
          case DashboardsDatabase::DISCUSSIONS_DASHBOARD_TYPE:
            $entity_type_id = 'node';
            $entity_query = $this->entityTypeManager->getStorage($entity_type_id)
              ->getQuery()
              ->condition('type', 'discussion')
              ->accessCheck(FALSE);
            $data_table = $this->entityTypeManager->getStorage($entity_type_id)
              ->getDataTable();
            $entity_column_id = $this->entityTypeManager
              ->getStorage($entity_type_id)->getEntityType()->getKey('id');
            break;

        }
        if (!$data_table || !$entity_column_id) {
          $this->logger()
            ->critical("Schema for $dashboard_type entity was not found or has some errors.");
          return self::EXIT_FAILURE_WITH_CLARITY;
        }

        $dashboard_results = [];
        $ids = $entity_query->execute();
        foreach ($ids as $id) {
          $created_query = $this->connection->select($data_table);
          $created_query->addField($data_table, 'created');
          $created_query->addField($data_table, $entity_column_id);
          $created_query->condition("$data_table.$entity_column_id", $id);
          $results = $created_query->execute()->fetchAssoc();
          $monthKey = \Drupal::service('date.formatter')
              ->format($results['created'], 'custom', 'Y-m') . '-01';
          if (!isset($dashboard_results[$monthKey])) {
            $dashboard_results[$monthKey] = 1;
          }
          else {
            $dashboard_results[$monthKey]++;
          }
        }
        foreach ($dashboard_results as $monthKey => $count) {
          $this->dashboardCumulativeService->insertOrUpdate($dashboard_type, $monthKey, $count);
        }
        $this->dashboardCumulativeService->calculatePastStats($dashboard_type);
      }
    }

    return self::EXIT_SUCCESS;
  }

}
