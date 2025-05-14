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
 * Displays statistics for Group type "Event".
 */
class GroupEventsDashboardController extends ControllerBase {

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
    $groupType = 'event';

    // Define days, range, membership and flags.
    $lastDaysLimit = 90;
    $topGroupsLimit = 10;
    $membershipType = 'event-group_membership';
    $likeFlag = 'recommend_group';

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
    $groupsCumulativeStats = $this->dashboardCumulativeService->getCumulativeStatsPerDashboardType(DashboardsDatabase::EVENTS_DASHBOARD_TYPE);
    $groupsData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($groupsCumulativeStats));
    $groupsEvolution = $this->dashboardBuilder->chartLine($this->t('Events - evolution over time'), $groupsData);

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $groupsStats,
        $groupsEvolution,
      ], 1),
    ];

    // Groups by type chart.
    $typeField = 'field_vocab_event_type';
    $groupsByTypeData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $typeField));
    $groupsByType = $this->dashboardBuilder->chartPie($this->t('Events by type'), $groupsByTypeData, '');

    // Groups by most members.
    $topGroupsByMembersData = $this->dashboardHelper->jsonEncodeCategoriesSeries    ($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByMembers($membershipType, $topGroupsLimit)));
    $topGroupsByMembers = $this->dashboardBuilder->chartColumn( $this->t('Top @limit events with most members registered', ['@limit' => $topGroupsLimit]), $topGroupsByMembersData, true);

    // Groups by topic chart.
    $topicField = 'field_vocab_topics';
    $groupsByTopicData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $topicField));
    $groupsByTopic = $this->dashboardBuilder->chartPie($this->t('Events by topic'), $groupsByTopicData, '');

    // Groups by visibility chart.
    $groupsByVisibilityData = json_encode($this->groupStatistics->getGroupsByVisibility($groupType));
    $groupsByVisibility = $this->dashboardBuilder->chartPie($this->t('Events by visibility'), $groupsByVisibilityData,
      '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $groupsByType,
        $topGroupsByMembers,
        $groupsByTopic,
        $groupsByVisibility,
      ], 2),
    ];

    // Groups by country.
    $locationField = 'field_location';
    $groupsByCountryData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getGroupsGroupedByLocation($groupType, $locationField, 'id')));
    $groupsByCountryChart = $this->dashboardBuilder->chartColumn($this->t('Events by country'), $groupsByCountryData,
      false);

    // Section 3.
    $section3Build = [
      $this->dashboardBuilder->columns([
        $groupsByCountryChart,
      ], 1),
    ];

    // Top topics of groups.
    $topTopicsOfGroupsData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopTermsOfGroups($groupType, $topicField)));
    $topTopicsOfGroups = $this->dashboardBuilder->chartColumn( $this->t('Top @limit event topics', ['@limit' => $topGroupsLimit]), $topTopicsOfGroupsData, true);

    // Most liked groups.
    $topGroupsByLikesLink = $this->dashboardBuilder->buttonToView('view.admin_groups.page_admin_events', '', '', $this->t('See all'));
    $topGroupsByLikes = $this->dashboardBuilder->titleLinkList($this->t('Most liked events'), $topGroupsByLikesLink, $this->groupStatistics->getTopGroupsByFlag($groupType, $likeFlag, $topGroupsLimit, 'list'));

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $topTopicsOfGroups,
        $topGroupsByLikes,
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
