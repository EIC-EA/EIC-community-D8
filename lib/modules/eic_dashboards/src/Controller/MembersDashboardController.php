<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Constants\DashboardFilters;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardCumulativeService;
use Drupal\eic_dashboards\Services\MembersStatisticsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Drupal\eic_user\UserHelper;

/**
 * Provides route responses for the eic_dashboards module.
 */
class MembersDashboardController extends ControllerBase
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
   * The platform statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\MembersStatisticsInterface
   */
  protected MembersStatisticsInterface $membersStatistics;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

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
    DashboardBuilderInterface  $dashboardBuilder,
    DashboardHelperInterface   $dashboardHelper,
    MembersStatisticsInterface $membersStatistics,
    UserHelper $eicUserHelper,
    DashboardCumulativeService $dashboardCumulativeService,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->membersStatistics = $membersStatistics;
    $this->eicUserHelper = $eicUserHelper;
    $this->dashboardCumulativeService = $dashboardCumulativeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.members_statistics'),
      $container->get('eic_user.helper'),
      $container->get('eic_dashboards.cumulative'),
    );
  }

  /**
   * Members overview.
   */
  public function membersOverview(): array {
    if ($cache = $this->cache()->get('dashboard:members')) {
      $content = $cache->data;
    }
    else {
      // ===== Section 1.
      // Platform members.
      $totalMembers = $this->membersStatistics->getTotalMembers();
      $membersLink = $this->dashboardBuilder->buttonToView('view.dashboard_members_list.page', '', '', $this->t('Members list'));
      $members = $this->dashboardBuilder->numberAndLink($this->t('Platform members'), $totalMembers, $membersLink);

      // Joined in the past 30 days.
      $days = 30;
      $membersCreatedPastDaysData = $this->membersStatistics->getMembersRegisteredPastDays($days);
      $membersCreatedPastDaysLink = $this->dashboardBuilder->buttonToView('view.dashboard_members_list.page', 'created[min]', gmdate("Y-m-d", strtotime("-$days days")), $this->t('Members list'));
      $membersCreatedPastDays = $this->dashboardBuilder->numberAndLink($this->t('Joined in the past @days days', ['@days' => $days]), $membersCreatedPastDaysData, $membersCreatedPastDaysLink);

      // Recently logged in.
      $days = 30;
      $membersLoggedPastDaysData = $this->membersStatistics->getPlatformMembersLoggedPastDays($days);
      $membersLoggedPastDaysLink = $this->dashboardBuilder->buttonToView('view.dashboard_members_list.page', 'access[min]', gmdate("Y-m-d", strtotime("-$days days")), $this->t('Members list'));
      $membersLoggedPastDays = $this->dashboardBuilder->numberAndLink($this->t('Recently logged in'), $membersLoggedPastDaysData, $membersLoggedPastDaysLink);

      $membersStats = [
        $this->dashboardBuilder->columns([
          $members,
          $membersCreatedPastDays,
          $membersLoggedPastDays,
        ], 3),
      ];

      // Members evolution.
      $membersCumulativeStats = $this->dashboardCumulativeService->getCumulativeStatsPerDashboardType(DashboardsDatabase::MEMBERS_DASHBOARD_TYPE);
      $membersData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($membersCumulativeStats));
      $membersEvolution = $this->dashboardBuilder->chartLine($this->t('Members - evolution over time'), $membersData);

      $section1Build = [
        $this->dashboardBuilder->columns([
          $membersStats,
          $membersEvolution
        ], 1),
      ];

      // ===== Section 2.
      // Members by country.
      $membersByCountryData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->membersStatistics->getMembersGroupedByCountry('id')));
      $membersByCountryChart = $this->dashboardBuilder->chartColumn($this->t('Members by country'), $membersByCountryData, false);
      $membersByCountryMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->membersStatistics->getMembersGroupedByCountry(DashboardFilters::DASHBOARD_PROJECTS_LIST_COUNTRY), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_PROJECTS_LIST_COUNTRY]);
      $membersByCountryMenu = $this->dashboardBuilder->jumpMenu($this->t('List members of'), $this->t('Choose a country'), $membersByCountryMenuData);
      $membersByCountry = $this->dashboardBuilder->chartWithMenu($membersByCountryChart, $membersByCountryMenu);

      $section2Build = $this->dashboardBuilder->columns([$membersByCountry], 1);

      // ===== Section 3.
      // Members by organisation type.
      $membersByUserTypeData = json_encode($this->dashboardHelper->transformLabelCountToNameAndY($this->membersStatistics->getMembersPerTaxonomyTerm('field_vocab_user_type', 'id')));
      $membersByUserTypeChart = $this->dashboardBuilder->chartPie($this->t('Members by type'), $membersByUserTypeData, '');
      $membersByUserTypeMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->membersStatistics->getMembersPerTaxonomyTerm('field_vocab_user_type', DashboardFilters::DASHBOARD_MEMBERS_LIST_USER_TYPE), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_USER_TYPE]);
      $membersByUserTypeMenu = $this->dashboardBuilder->jumpMenu($this->t('List members by type'), $this->t('Choose expertise'), $membersByUserTypeMenuData);
      $membersByOrganizationType = $this->dashboardBuilder->chartWithMenu($membersByUserTypeChart, $membersByUserTypeMenu);

      // Members by topic of Interest.
      $membersByTopicOfInterestData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->membersStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_interest', 'id'), 'topics'));
      $membersByTopicOfInterestChart = $this->dashboardBuilder->chartPie($this->t('Members by topic of interest'), $membersByTopicOfInterestData, '');
      $membersByTopicOfInterestMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->membersStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_interest', DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST]);
      $membersByTopicOfInterestMenu = $this->dashboardBuilder->jumpMenu($this->t('List members by topic of interest'), $this->t('Choose interest'), $membersByTopicOfInterestMenuData);
      $membersByTopicOfInterest = $this->dashboardBuilder->chartWithMenu($membersByTopicOfInterestChart, $membersByTopicOfInterestMenu);

      // Members by topic of Expertise.
      $membersByTopicOfExpertiseData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->membersStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_expertise', 'id'),'topics'));
      $membersByTopicOfExpertiseChart = $this->dashboardBuilder->chartPie($this->t('Members by topic of expertise'), $membersByTopicOfExpertiseData, '');
      $membersByTopicOfExpertiseMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->membersStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_expertise', DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_EXPERTISE), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST]);
      $membersByTopicOfExpertiseMenu = $this->dashboardBuilder->jumpMenu($this->t('List members by topic of expertise'), $this->t('Choose expertise'), $membersByTopicOfExpertiseMenuData);
      $membersByTopicOfExpertise = $this->dashboardBuilder->chartWithMenu($membersByTopicOfExpertiseChart, $membersByTopicOfExpertiseMenu);

      $totalCompletedMembersProfiles = $this->eicUserHelper->getMemberProfileCompletionCount();
      $totalCompletedMembersProfilesData = json_encode([
        ['name' => 'Completed profile', 'y' => $totalCompletedMembersProfiles],
        ['name' => 'Incomplete profile', 'y' => ($totalMembers - $totalCompletedMembersProfiles)],
      ], JSON_NUMERIC_CHECK);
      $totalCompletedMembersProfilesChart = $this->dashboardBuilder->chartPie($this->t('Profile completion status'), $totalCompletedMembersProfilesData, '');

      // Members linked to Organisations
      $membersLinkedToOrganisations = $this->membersStatistics->getMembersLinkedByType('organisation-group_membership');
      $membersLinkedToOrganisationsData = json_encode([
        ['name' => 'Linked to at least one EIC Community organisation', 'y' => $membersLinkedToOrganisations],
        ['name' => 'Not linked to any Organisation', 'y' => ($totalMembers - $membersLinkedToOrganisations)],
      ], JSON_NUMERIC_CHECK);
      $membersLinkedToOrganisationsChart = $this->dashboardBuilder->chartPie($this->t('Members linked to Organisations'), $membersLinkedToOrganisationsData, '');

      // Members linked to Projects
      $membersLinkedToProjects = $this->membersStatistics->getMembersLinkedByType('project-group_membership');
      $membersLinkedToProjectsData = json_encode([
        ['name' => 'Linked to at least one EIC Community organisation', 'y' => $membersLinkedToProjects],
        ['name' => 'Not linked to any Organisation', 'y' => ($totalMembers - $membersLinkedToProjects)],
      ], JSON_NUMERIC_CHECK);
      $membersLinkedToProjectsChart = $this->dashboardBuilder->chartPie($this->t('Members linked to Projects'), $membersLinkedToProjectsData, '');

      $section3Build = $this->dashboardBuilder->columns([
        $membersByOrganizationType,
        $membersByTopicOfInterest,
        $membersByTopicOfExpertise,
        $totalCompletedMembersProfilesChart,
        $membersLinkedToOrganisationsChart,
        $membersLinkedToProjectsChart
      ], 2);

      // ===== Section 4.
      $maxResults = 10;
      $lastRegisteredMembersList = $this->dashboardBuilder->titleLinkList($this->t('Latest members'), '', $this->membersStatistics->getMembersListInGivenPeriod('', '', $maxResults));

      $section4Build = [
        $this->dashboardBuilder->columns([
          $lastRegisteredMembersList,
        ], 2),
      ];

      $content = [
        $section1Build,
        $section2Build,
        $section3Build,
        $section4Build,
      ];
      $this->cache()->set('dashboard:members', $content);
    }

    return [
      'content' => $content,
    ];
  }
}
