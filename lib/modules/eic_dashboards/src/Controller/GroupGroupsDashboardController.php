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
 * Displays statistics for Group type "Group".
 */
class GroupGroupsDashboardController extends ControllerBase {

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
    $groupType = 'group';

    // Define days, range, membership, content type and flags.
    $lastDaysLimit = 90;
    $topGroupsLimit = 10;
    $membershipType = 'group-group_membership';
    $discussionType = 'group-group_node-discussion';
    $eventType = 'group-group_node-event';
    $documentType = 'group-group_node-document';
    $likeFlag = 'recommend_group';
    $followFlag = 'follow_group';

    if ($cache = $this->cache()->get('dashboard:groups')) {
      $content = $cache->data;
    }
    else {
      // Number of groups.
      $numberOfGroupsData = $this->groupStatistics->getNumberOfGroups($groupType);
      $numberOfGroups = $this->dashboardBuilder->numberAndLink($this->t('Total @groups', ['@group' => $groupType]),
        $numberOfGroupsData, '');

      // Number of groups created in past days.
      $numberOfGroupsPastDaysData = $this->groupStatistics->getNumberOfGroupsPastDays($groupType, $lastDaysLimit);
      $numberOfGroupsPastDays = $this->dashboardBuilder->numberAndLink($this->t('New @groups - last @days days', [
        '@group' => $groupType,
        '@days' =>
          $lastDaysLimit
      ]), $numberOfGroupsPastDaysData, '');

      $groupsStats = [
        $this->dashboardBuilder->columns([
          $numberOfGroups,
          $numberOfGroupsPastDays,
        ], 3),
      ];

      // Groups evolution.
      $groupsCumulativeStats = $this->dashboardCumulativeService->getCumulativeStatsPerDashboardType(DashboardsDatabase::GROUPS_DASHBOARD_TYPE);
      $groupsData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($groupsCumulativeStats));
      $groupsEvolution = $this->dashboardBuilder->chartLine($this->t('Groups - evolution over time'), $groupsData);

      // Section 1.
      $section1Build = [
        $this->dashboardBuilder->columns([
          $groupsStats,
          $groupsEvolution,
        ], 1),
      ];

      // Groups by moderation status chart.
      $groupsByStatusData = json_encode($this->groupStatistics->getGroupsByStatus($groupType));
      $groupsByStatus = $this->dashboardBuilder->chartPie($this->t('Groups by status'), $groupsByStatusData, '');

      // Groups by visibility chart.
      $groupsByVisibilityData = json_encode($this->groupStatistics->getGroupsByVisibility($groupType));
      $groupsByVisibility = $this->dashboardBuilder->chartPie($this->t('Published groups by visibility'), $groupsByVisibilityData, '');

      // Section 2.
      $section2Build = [
        $this->dashboardBuilder->columns([
          $groupsByStatus,
          $groupsByVisibility,
        ], 2),
      ];

      // Top 10 groups by number of members.
      $topGroupsByMembers = $this->dashboardHelper->jsonEncodeCategoriesSeries
      ($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByMembers($membershipType, $topGroupsLimit)));
      $topGroupsByMembersChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of members', ['@limit' => $topGroupsLimit]), $topGroupsByMembers, TRUE);

      // Top 10 groups by number of discussions.
      $topGroupsByDiscussions = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByContentType($discussionType, $topGroupsLimit)));
      $topGroupsByDiscussionsChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of discussions', ['@limit' => $topGroupsLimit]), $topGroupsByDiscussions, TRUE);

      // Top 10 groups by number of events.
      $topGroupsByEvents = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByContentType($eventType, $topGroupsLimit)));
      $topGroupsByEventsChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of events',
        ['@limit' => $topGroupsLimit]), $topGroupsByEvents, TRUE);

      // Top 10 groups by number of documents.
      $topGroupsByDocuments = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByContentType($documentType, $topGroupsLimit)));
      $topGroupsByDocumentsChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of documents',
        ['@limit' => $topGroupsLimit]), $topGroupsByDocuments, TRUE);

      // Top 10 groups by number of likes.
      $topGroupsByLikes = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByFlag($groupType, $likeFlag, $topGroupsLimit)));
      $topGroupsByLikesChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of likes', ['@limit' => $topGroupsLimit]), $topGroupsByLikes, TRUE);

      // Top 10 groups by number of follow.
      $topGroupsByFollows = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByFlag($groupType, $followFlag, $topGroupsLimit)));
      $topGroupsByFollowsChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of follows', ['@limit' => $topGroupsLimit]), $topGroupsByFollows, TRUE);

      // Tab content 1.
      $tabContent1 = [
        $this->dashboardBuilder->columns([
          $topGroupsByMembersChart,
          $topGroupsByDiscussionsChart,
          $topGroupsByEventsChart,
          $topGroupsByDocumentsChart,
          $topGroupsByLikesChart,
          $topGroupsByFollowsChart,
        ], 2),
      ];

      // Top 10 groups by number of members in the past days.
      $topGroupsByMembersLastDays = $this->dashboardHelper->jsonEncodeCategoriesSeries
      ($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByMembers
      ($membershipType, $topGroupsLimit, $lastDaysLimit)));
      $topGroupsByMembersLastDaysChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of members', ['@limit' => $topGroupsLimit]), $topGroupsByMembersLastDays, TRUE);

      // Top 10 groups by number of discussions in the past days.
      $topGroupsByDiscussionsLastDays = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByContentType($discussionType, $topGroupsLimit, $lastDaysLimit)));
      $topGroupsByDiscussionsLastDaysChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of discussions', ['@limit' => $topGroupsLimit]), $topGroupsByDiscussionsLastDays, TRUE);

      // Top 10 groups by number of events in the past days.
      $topGroupsByEventsLastDays = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByContentType($eventType, $topGroupsLimit, $lastDaysLimit)));
      $topGroupsByEventsLastDaysChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of events', ['@limit' => $topGroupsLimit]), $topGroupsByEventsLastDays, TRUE);

      // Top 10 groups by number of documents in the past days.
      $topGroupsByDocumentsLastDays = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->groupStatistics->getTopGroupsByContentType($documentType, $topGroupsLimit, $lastDaysLimit)));
      $topGroupsByDocumentsLastDaysChart = $this->dashboardBuilder->chartColumn($this->t('Top @limit groups by number of documents', ['@limit' => $topGroupsLimit]), $topGroupsByDocumentsLastDays, TRUE);

      // Tab content 2.
      $tabContent2 = [
        $this->dashboardBuilder->columns([
          $topGroupsByMembersLastDaysChart,
          $topGroupsByDiscussionsLastDaysChart,
          $topGroupsByEventsLastDaysChart,
          $topGroupsByDocumentsLastDaysChart,
        ], 2),
      ];

      // Tabs.
      $tabsItems = [
        [
          'title' => 'All time',
          'content' => $tabContent1
        ],
        [
          'title' => 'Last ' . $lastDaysLimit . ' days',
          'content' => $tabContent2
        ]
      ];

      // Section 3.
      $section3Build = $this->dashboardBuilder->tabs('Top ' . $topGroupsLimit . ' metrics', $tabsItems);
      $content = [
        $section1Build,
        $section2Build,
        $section3Build,
      ];
      $this->cache()->set('dashboard:groups', $content);
    }

    return [
      'content' => $content,
    ];
  }
}
