<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Constants\DashboardFilters;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\PlatformStatisticsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;

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
   * @var \Drupal\eic_dashboards\Services\PlatformStatisticsInterface
   */
  protected PlatformStatisticsInterface $platformStatistics;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface $dashboardHelper,
    PlatformStatisticsInterface $platformStatistics,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->platformStatistics = $platformStatistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.platform_statistics'),
    );
  }

  /**
   * Members overview.
   */
  public function membersOverview(): array {
    // ===== Section 1.
    // Platform members.
    $platformMembersData = $this->platformStatistics->getTotalPlatformMembers();
    $platformMembersLink = $this->dashboardBuilder->buttonToView('view.dashboard_members_list.page', '', '', $this->t('Members list'));
    $platformMembers = $this->dashboardBuilder->numberAndLink($this->t('Platform members'), $platformMembersData, $platformMembersLink);

    $section1Build = [
      $this->dashboardBuilder->columns([
        $platformMembers,
      ], 3),
    ];

    // ===== Section 2.
    // Members by country.
    $membersByCountryData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->platformStatistics->getMembersGroupedByCountry('id')));
    $membersByCountryChart = $this->dashboardBuilder->chartColumn($this->t('Members by country'), $membersByCountryData);
    $membersByCountryMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->platformStatistics->getMembersGroupedByCountry(DashboardFilters::DASHBOARD_MEMBERS_LIST_COUNTRY), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_COUNTRY]);
    $membersByCountryMenu = $this->dashboardBuilder->jumpMenu($this->t('List members of'), $this->t('Choose a country'), $membersByCountryMenuData);
    $membersByCountry = $this->dashboardBuilder->chartWithMenu($membersByCountryChart, $membersByCountryMenu);

    $section2Build = $this->dashboardBuilder->columns([$membersByCountry], 1);

    // ===== Section 3.
    // Members by organisation type.
    $membersByUserTypeData = json_encode($this->dashboardHelper->transformLabelCountToNameAndY($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_user_type', 'id')));
    $membersByUserTypeChart = $this->dashboardBuilder->chartPie($this->t('Members by type'), $membersByUserTypeData, '');
    $membersByUserTypeMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_user_type', DashboardFilters::DASHBOARD_MEMBERS_LIST_USER_TYPE), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_USER_TYPE]);
    $membersByUserTypeMenu = $this->dashboardBuilder->jumpMenu($this->t('List members by type'), $this->t('Choose expertise'), $membersByUserTypeMenuData);
    $membersByOrganizationType = $this->dashboardBuilder->chartWithMenu($membersByUserTypeChart, $membersByUserTypeMenu);

    // Members by topic of Interest.
    $membersByTopicOfInterestData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_interest', 'id'), 'topics'));
    $membersByTopicOfInterestChart = $this->dashboardBuilder->chartPie($this->t('Members by topic of interest'), $membersByTopicOfInterestData, '');
    $membersByTopicOfInterestMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_interest', DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST]);
    $membersByTopicOfInterestMenu = $this->dashboardBuilder->jumpMenu($this->t('List members by topic of interest'), $this->t('Choose interest'), $membersByTopicOfInterestMenuData);
    $membersByTopicOfInterest = $this->dashboardBuilder->chartWithMenu($membersByTopicOfInterestChart, $membersByTopicOfInterestMenu);

    // Members by topic of Expertise.
    $membersByTopicOfExpertiseData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_expertise', 'id'),'topics'));
    $membersByTopicOfExpertiseChart = $this->dashboardBuilder->chartPie($this->t('Members by topic of expertise'), $membersByTopicOfExpertiseData, '');
    $membersByTopicOfExpertiseMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_topic_expertise', DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_EXPERTISE), 'view.dashboard_members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_TOPIC_OF_INTEREST]);
    $membersByTopicOfExpertiseMenu = $this->dashboardBuilder->jumpMenu($this->t('List members by topic of expertise'), $this->t('Choose expertise'), $membersByTopicOfExpertiseMenuData);
    $membersByTopicOfExpertise = $this->dashboardBuilder->chartWithMenu($membersByTopicOfExpertiseChart, $membersByTopicOfExpertiseMenu);

    $section3Build = $this->dashboardBuilder->columns([
      $membersByOrganizationType,
      $membersByTopicOfInterest,
      $membersByTopicOfExpertise
    ], 2);

    // Build sections
    $build = [
      'content' => [
        $section1Build,
        $section2Build,
        $section3Build,
      ],
    ];

    return $build;
  }
}
