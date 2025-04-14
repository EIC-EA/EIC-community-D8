<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Drupal\eic_dashboards\Services\GroupStatisticsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays statistics for Group type "Group".
 */
class GroupGroupsDashboardController extends ControllerBase
{

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
  public static function create(ContainerInterface $container)
  {
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
    $groupType = 'group';

    // Number of groups.
    $numberOfGroupsData = $this->groupStatistics->getNumberOfGroups($groupType);
    $numberOfGroups = $this->dashboardBuilder->numberAndLink($this->t('Total @groupss', ['@groups' => $groupType]),
      $numberOfGroupsData, '');

    // Number of groups created in past 30 days.
    $lastDaysLimit = 30;
    $numberOfGroupsPastDaysData = $this->groupStatistics->getNumberOfGroupsPastDays($groupType, $lastDaysLimit);
    $numberOfGroupsPastDays = $this->dashboardBuilder->numberAndLink($this->t('New @groupss - last @days days', ['@groups' => $groupType, '@days' =>
      $lastDaysLimit]), $numberOfGroupsPastDaysData, '');

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $numberOfGroups,
        $numberOfGroupsPastDays,
      ], 3),
    ];

    // Groups by moderation status chart.
    $groupsByStatusData = json_encode($this->groupStatistics->getGroupsByStatus($groupType));
    $groupsByStatus = $this->dashboardBuilder->chartPie($this->t('Groups by status'), $groupsByStatusData, '');

    // Groups by moderation status chart.
    $groupsByVisibilityData = json_encode($this->groupStatistics->getGroupsByVisibility($groupType));
    $groupsByVisibility = $this->dashboardBuilder->chartPie($this->t('Groups by visibility'), $groupsByVisibilityData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $groupsByStatus,
        $groupsByVisibility,
      ], 2),
    ];

    $build = [
      'content' => [
        $section1Build,
        $section2Build,
      ],
    ];

    return $build;
  }
}
