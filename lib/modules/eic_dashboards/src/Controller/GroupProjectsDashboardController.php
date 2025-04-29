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
    $likeFlag = 'recommend_group';

    // Number of groups.
    $numberOfGroupsData = $this->groupStatistics->getNumberOfGroups($groupType);
    $numberOfGroups = $this->dashboardBuilder->numberAndLink($this->t('Total @groups', ['@group' => $groupType]), $numberOfGroupsData, '');

    // Number of groups created in past days.
    $numberOfGroupsPastDaysData = $this->groupStatistics->getNumberOfGroupsPastDays($groupType, $lastDaysLimit);
    $numberOfGroupsPastDays = $this->dashboardBuilder->numberAndLink($this->t('New @groups - last @days days', ['@group' => $groupType, '@days' => $lastDaysLimit]), $numberOfGroupsPastDaysData, '');

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

    // Most liked groups.
    $topGroupsByLikesLink = $this->dashboardBuilder->buttonToView('view.admin_groups.page_admin_projects', '', '', $this->t('See all'));
    $topGroupsByLikes = $this->dashboardBuilder->titleLinkList($this->t('Most liked projects'), $topGroupsByLikesLink, $this->groupStatistics->getTopGroupsByFlag($groupType, $likeFlag, $topGroupsLimit, 'list'));

    // Groups by field of science chart.
    $scienceField = 'field_project_fields_of_science';
    $groupsByScienceData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $scienceField));
    $groupsByScience = $this->dashboardBuilder->chartPie($this->t('Projects by field of science'), $groupsByScienceData, '');

    // Groups by status chart.
    $statusField = 'field_project_status';
    $groupsByStatusData = json_encode($this->groupStatistics->getGroupsPerValue($groupType, $statusField));
    $groupsByStatus = $this->dashboardBuilder->chartPie($this->t('Projects status'), $groupsByStatusData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $groupsByFunding,
        $topGroupsByLikes,
        $groupsByScience,
        $groupsByStatus,
      ], 2),
    ];

    // Groups by Horizon Platform results.
    $resultsField = 'field_project_horizon_results';
    $groupsWithResults = $this->groupStatistics->getNumberOfGroupsWithPopulatedField($groupType, $resultsField);
    $groupsWithoutResults = $this->groupStatistics->getNumberOfGroups($groupType) - $groupsWithResults;
    $groupsByResultsData = json_encode([
      [
        'name' => 'Includes HRP reference',
        'y' => (int) $groupsWithResults,
      ],
      [
        'name' => 'Without HRP reference',
        'y' => (int) $groupsWithoutResults,
      ]
    ]);
    $groupsByResults = $this->dashboardBuilder->chartPie($this->t('Projects with Horizon Platform Results'),$groupsByResultsData, '');

    // Groups by Innovation Radar results.
    $innovationsField = 'field_project_innovations';
    $groupsWithInnovations = $this->groupStatistics->getNumberOfGroupsWithPopulatedField($groupType, $innovationsField);
    $groupsWithoutInnovations = $this->groupStatistics->getNumberOfGroups($groupType) - $groupsWithInnovations;
    $groupsByInnovationsData = json_encode([
      [
        'name' => 'Recognized by Innovation Radar',
        'y' => (int) $groupsWithInnovations,
      ],
      [
        'name' => 'Not recognized by Innovation Radar',
        'y' => (int) $groupsWithoutInnovations,
      ]
    ]);
    $groupsByInnovations = $this->dashboardBuilder->chartPie($this->t('Projects recognized by Innovation Radar'),$groupsByInnovationsData, '');

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $groupsByResults,
        $groupsByInnovations,
      ], 2),
    ];

    $build = [
      'content' => [
        $section1Build,
        $section2Build,
        $section4Build,
      ],
    ];

    return $build;
  }
}
