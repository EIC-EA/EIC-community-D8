<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Constants\DashboardFilters;
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

    // Define days, range and flags.
    $lastDaysLimit = 30;
    $topGroupsLimit = 10;
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

    // Groups by location of coordinating organisation.
    $groupsByLocationOfOrganisationData =
      $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getProjectsGroupedByLocationOfOrganisation('id')));
    $groupsByLocationOfOrganisationChart = $this->dashboardBuilder->chartColumn($this->t('Projects by coordinating organisation country'), $groupsByLocationOfOrganisationData, false);
    $groupsByLocationOfOrganisationMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->groupStatistics->getProjectsGroupedByLocationOfOrganisation(DashboardFilters::DASHBOARD_PROJECTS_LIST_COUNTRY), 'view.dashboard_projects_list.page', [], [DashboardFilters::DASHBOARD_PROJECTS_LIST_COUNTRY]);
    $groupsByLocationOfOrganisationMenu = $this->dashboardBuilder->jumpMenu($this->t('List projects of'), $this->t('Choose a country'), $groupsByLocationOfOrganisationMenuData);
    $groupsByLocationOfOrganisation = $this->dashboardBuilder->chartWithMenu($groupsByLocationOfOrganisationChart, $groupsByLocationOfOrganisationMenu);

    // Section 3.
    $section3Build = [
      $this->dashboardBuilder->columns([
        $groupsByLocationOfOrganisation,
      ], 1),
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
    $groupsByResults = $this->dashboardBuilder->chartPie($this->t('Projects with Horizon Platform Results'), $groupsByResultsData, '');

    // Groups by Innovation Radar recognition.
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
    $groupsByInnovations = $this->dashboardBuilder->chartPie($this->t('Projects recognized by Innovation Radar'), $groupsByInnovationsData, '');

    // Groups linked to organisations.
    $groupsLinkedToOrganisations = $this->groupStatistics->getNumberOfProjectsLinkedToOrganisations();
    $groupsNotLinkedToOrganisations = $this->groupStatistics->getNumberOfGroups($groupType) - $groupsLinkedToOrganisations;
    $groupsByLinkToOrganisationData = json_encode([
      [
        'name' => 'Linked to at least one EIC Community organisation',
        'y' => (int) $groupsLinkedToOrganisations,
      ],
      [
        'name' => 'Not linked to any organisations',
        'y' => (int) $groupsNotLinkedToOrganisations,
      ]
    ]);
    $groupsByLinkToOrganisation = $this->dashboardBuilder->chartPie($this->t('Projects linked to organisations'), $groupsByLinkToOrganisationData, '');

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $groupsByResults,
        $groupsByInnovations,
        $groupsByLinkToOrganisation,
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
