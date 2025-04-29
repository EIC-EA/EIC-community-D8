<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Drupal\eic_dashboards\Services\GroupStatisticsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays statistics for Group type "Project".
 */
class GroupProjectsDashboardController extends ControllerBase {

  /**
   * The dashboards builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardBuilderInterface
   */
  protected DashboardBuilderInterface $dashboardBuilder;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardHelperInterface
   */
  protected DashboardHelperInterface $dashboardHelper;

  /**
   * The content statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\GroupStatisticsInterface
   */
  protected GroupStatisticsInterface $groupStatistics;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface $dashboardHelper,
    GroupStatisticsInterface $groupStatistics,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->groupStatistics = $groupStatistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.group_statistics'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function page(): array {
    // Specify current group type.
    $groupType = 'project';

    // Define days, range, membership and flags.
    $lastDaysLimit = 90;
    $topGroupsLimit = 10;
    $membershipType = 'project-group_membership';

    // Number of groups.
    $numberOfGroupsData = $this->groupStatistics->getNumberOfGroups($groupType);
    $numberOfGroups = $this->dashboardBuilder->numberAndLink($this->t('Total @groups', ['@group' => $groupType]),
      $numberOfGroupsData, '');

    // Number of groups created in past days.
    $numberOfGroupsPastDaysData = $this->groupStatistics->getNumberOfGroupsPastDays($groupType, $lastDaysLimit);
    $numberOfGroupsPastDays = $this->dashboardBuilder->numberAndLink($this->t('New @groups - last @days days', ['@group' => $groupType, '@days' =>
      $lastDaysLimit]), $numberOfGroupsPastDaysData, '');

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $numberOfGroups,
        $numberOfGroupsPastDays,
      ], 3),
    ];

    // Groups by funding programme chart.
    $fundingField = 'field_project_funding_programme';
    $groupsByFundingData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $fundingField));
    $groupsByFunding = $this->dashboardBuilder->chartPie($this->t('Projects by funding programme'), $groupsByFundingData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $groupsByFunding,
      ], 2),
    ];

    $build = [
      'content' => [
        $section1Build,
        $section2Build
      ],
    ];

    return $build;
  }
}
