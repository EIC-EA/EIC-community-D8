<?php

namespace Drupal\eic_dashboards\Drush\Commands;

use Drupal\eic_dashboards\Services\DashboardCumulativeService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class GenerateCumulativeStatsMonthDrushCommands extends DrushCommands {

  /**
   * Constructs a GenerateCumulativeStatsMonth object.
   */
  public function __construct(
    private readonly DashboardCumulativeService $dashboardCumulativeService,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.cumulative'),
    );
  }

  /**
   * Populate the data of the previous month into the database.
   */
  #[CLI\Command(name: 'eic_dashboards:generate-all-previous-month', aliases: ['dashboard-previous-month'])]
  public function generateAllStatsPreviousMonth($options = ['format' => 'table']) {

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
        $this->dashboardCumulativeService->calculatePastStats($dashboardType);
        $this->logger()->success(t("Generated data for {$startDate->format('Y-m')} for dashboard type '$dashboardType'"));
      }
      else {
        $this->logger()->error(t("Could not generate data for {$startDate->format('Y-m')} for dashboard type '$dashboardType'"));
      }
    }

  }

}
