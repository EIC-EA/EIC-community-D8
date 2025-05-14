<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardCumulativeService;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Drupal\eic_dashboards\Services\GroupStatisticsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays statistics for Group type "Organisation".
 */
class GroupOrganisationsDashboardController extends ControllerBase {

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
   * The dashboard cumulative statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardCumulativeService
   */
  protected DashboardCumulativeService $dashboardCumulativeService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface $dashboardHelper,
    GroupStatisticsInterface $groupStatistics,
    DashboardCumulativeService $dashboardCumulativeService,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->groupStatistics = $groupStatistics;
    $this->dashboardCumulativeService = $dashboardCumulativeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.group_statistics'),
      $container->get('eic_dashboards.cumulative'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function page(): array {
    // Specify current group type.
    $groupType = 'organisation';

    // Define days, range, membership and flags.
    $lastDaysLimit = 90;
    $topGroupsLimit = 10;
    $membershipType = 'organisation-group_membership';

    // Number of groups.
    $numberOfGroupsData = $this->groupStatistics->getNumberOfGroups($groupType);
    $numberOfGroups = $this->dashboardBuilder->numberAndLink($this->t('Total @groups', ['@group' => $groupType]),
      $numberOfGroupsData, '');

    // Number of groups created in past days.
    $numberOfGroupsPastDaysData = $this->groupStatistics->getNumberOfGroupsPastDays($groupType, $lastDaysLimit);
    $numberOfGroupsPastDays = $this->dashboardBuilder->numberAndLink($this->t('New @groups - last @days days', ['@group' => $groupType, '@days' =>
      $lastDaysLimit]), $numberOfGroupsPastDaysData, '');

    $groupsStats = [
      $this->dashboardBuilder->columns([
        $numberOfGroups,
        $numberOfGroupsPastDays,
      ], 3),
    ];

    // Groups evolution.
    $groupsCumulativeStats = $this->dashboardCumulativeService->getCumulativeStatsPerDashboardType(DashboardsDatabase::ORGANISATIONS_DASHBOARD_TYPE);
    $groupsData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($groupsCumulativeStats));
    $groupsEvolution = $this->dashboardBuilder->chartLine($this->t('Organisations - evolution over time'), $groupsData);

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $groupsStats,
        $groupsEvolution,
      ], 1),
    ];

    // Groups by type chart.
    $typeField = 'field_organisation_type';
    $groupsByTypeData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $typeField));
    $groupsByType = $this->dashboardBuilder->chartPie($this->t('Organisations by type'), $groupsByTypeData, '');

    // Groups by most members.
    $topGroupsByMembersData = $this->dashboardHelper->jsonEncodeCategoriesSeries    ($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByMembers($membershipType, $topGroupsLimit)));
    $topGroupsByMembers = $this->dashboardBuilder->chartColumn( $this->t('Top @limit organisations with most members registered', ['@limit' => $topGroupsLimit]), $topGroupsByMembersData, true);

    // Groups by topic chart.
    $topicField = 'field_vocab_topics';
    $groupsByTopicData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $topicField));
    $groupsByTopic = $this->dashboardBuilder->chartPie($this->t('Organisations by topic'), $groupsByTopicData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $groupsByType,
        $topGroupsByMembers,
        $groupsByTopic,
      ], 2),
    ];

    // Groups by country.
    $locationField = 'field_address';
    $groupsByCountryData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getGroupsGroupedByLocation($groupType, $locationField, 'id')));
    $groupsByCountryChart = $this->dashboardBuilder->chartColumn($this->t('Organisations by country'), $groupsByCountryData, false);

    // Section 3.
    $section3Build = [
      $this->dashboardBuilder->columns([
        $groupsByCountryChart,
      ], 1),
    ];

    // Groups with project.
    $projectField = 'field_organisation_project_id';
    $groupsWithProject = $this->groupStatistics->getNumberOfGroupsWithPopulatedField($groupType, $projectField);
    $groupsWithoutProject = $this->groupStatistics->getNumberOfGroups($groupType) - $groupsWithProject;
    $groupsByProjectData = json_encode([
      [
        'name' => 'Referencing one or more projects',
        'y' => (int) $groupsWithProject,
      ],
      [
        'name' => 'Without any projects referenced',
        'y' => (int) $groupsWithoutProject,
      ]
    ]);
    $groupsByProject = $this->dashboardBuilder->chartPie($this->t('Organisations with projects'),$groupsByProjectData, '');

    // Groups with members.
    $groupsWithMembers = $this->groupStatistics->getNumberOfGroupsWithMembers($membershipType);
    $groupsWithoutMembers = $this->groupStatistics->getNumberOfGroups($groupType) - $groupsWithMembers;
    $groupsByMembersData = json_encode([
      [
        'name' => 'With one or more members',
        'y' => (int) $groupsWithMembers,
      ],
      [
        'name' => 'Without any members',
        'y' => (int) $groupsWithoutMembers,
      ]
    ]);
    $groupsByMembers = $this->dashboardBuilder->chartPie($this->t('Organisations with members'),$groupsByMembersData, '');

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $groupsByProject,
        $groupsByMembers,
      ], 2),
    ];

    $build = [
      'content' => [
        $section1Build,
        $section2Build,
        $section3Build,
        $section4Build,
      ],
    ];

    return $build;
  }
}
