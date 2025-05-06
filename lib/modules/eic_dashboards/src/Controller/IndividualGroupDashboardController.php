<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_dashboards\Constants\DashboardFilters;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Drupal\eic_dashboards\Services\GroupStatisticsInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains functions that create individual group dashboard.
 */
class IndividualGroupDashboardController extends ControllerBase {

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardBuilderinterface
   */
  protected DashboardBuilderInterface $dashboardBuilder;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardHelperinterface
   */
  protected DashboardHelperInterface $dashboardHelper;

  /**
   * The group statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\GroupStatisticsInterface
   */
  protected GroupStatisticsInterface $groupStatistics;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface $dashboardHelper,
    GroupStatisticsInterface $groupStatistics,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->groupStatistics = $groupStatistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.group_statistics'),
    );
  }

  /**
   * Checks access to the individual group dashboard pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden
   *   The access result.
   */
  public function access(AccountInterface $account, GroupInterface $group) {
    $groupType = $group->getGroupType();

    if ($groupType->id() !== 'group') {
      return AccessResult::forbidden()
        ->addCacheableDependency($group);
    }

    // Allow access to power users.
    if (UserHelper::isPowerUser($account)) {
      return AccessResult::allowed()
        ->addCacheableDependency($group)
        ->addCacheableDependency($account);
    }

    $groupMembership = $group->getMember($account);
    if ($groupMembership instanceof GroupMembership) {
      foreach ($groupMembership->getRoles() as $group_role) {
        if ($group_role->id() === EICGroupsHelper::GROUP_ADMINISTRATOR_ROLE or $group_role->id() === EICGroupsHelper::GROUP_OWNER_ROLE) {
          return AccessResult::allowed()
            ->addCacheableDependency($group)
            ->addCacheableDependency($account);
        }
      }
    }

    return AccessResult::forbidden()
      ->addCacheableDependency($group)
      ->addCacheableDependency($account);
  }

  /**
   * Group dashboard title.
   */
  public function title(GroupInterface $group) {
    return $this->t('Group dashboard');
  }

  /**
   * Group dashboard page.
   */
  public function page(GroupInterface $group): array {
    // Define past days limit.
    $lastDaysLimit = 30;

    // Group members.
    $groupMembersData = $this->groupStatistics->getGroupMembers($group);
    $groupMembers = $this->dashboardBuilder->numberAndLink($this->t('Total members'), $groupMembersData, '');

    // Group members that joined in past days.
    $groupMembersJoinedInPastDaysData = $this->groupStatistics->getGroupMembersRegisteredPastDays($group,
      $lastDaysLimit);
    $groupMembersJoinedInPastDays = $this->dashboardBuilder->numberAndLink($this->t('Joined - past @limit days', ['@limit' => $lastDaysLimit]), $groupMembersJoinedInPastDaysData, '');

    // Group members that logged in past days.
    $groupMembersLoggedInPastDaysData = $this->groupStatistics->getGroupMembersLoggedPastDays($group, $lastDaysLimit);
    $groupMembersLoggedInPastDays = $this->dashboardBuilder->numberAndLink($this->t('Logged in - past @limit days', ['@limit' => $lastDaysLimit]), $groupMembersLoggedInPastDaysData, '');

    $section1Content = [
      $this->dashboardBuilder->columns([
        $groupMembers,
        $groupMembersJoinedInPastDays,
        $groupMembersLoggedInPastDays,
      ], 3),
    ];

    // Section 1.
    $section1Title = 'Members metrics';
    $section1Border = FALSE;
    $section1Build = [$this->dashboardBuilder->dashboardSection($section1Title, '', $section1Content, 'members', $section1Border)];

    // Group members by country.
    $groupMembersByCountryData = $this->dashboardHelper->jsonEncodeCategoriesSeries
    ($this->dashboardHelper->transformIdCountToCategoriesSeries
    ($this->groupStatistics->getGroupMembersGroupedByCountry($group, 'id', DashboardFilters::DASHBOARD_MEMBERS_LIST_GROUP_ID)));
    $groupMembersByCountryChart = $this->dashboardBuilder->chartColumn($this->t('Members by country'), $groupMembersByCountryData, false);

    $section2Build = [
      $this->dashboardBuilder->columns([
        $groupMembersByCountryChart,
      ], 1)
    ];

    // Group members by topics of expertise.
    $topicsVocabulary = 'topics';
    $topicsOfExpertiseField = 'field_vocab_topic_expertise';
    $groupMembersByExpertiseData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->groupStatistics->getGroupMembersPerTaxonomyTerm($group, $topicsOfExpertiseField, 'id', DashboardFilters::DASHBOARD_MEMBERS_LIST_GROUP_ID), $topicsVocabulary));
    $groupMembersByExpertiseChart = $this->dashboardBuilder->chartPie($this->t('Members by topics of expertise'), $groupMembersByExpertiseData, '');

    // Group members by topics of interest.
    $topicsOfInterestField = 'field_vocab_topic_interest';
    $groupMembersByInterestData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->groupStatistics->getGroupMembersPerTaxonomyTerm($group, $topicsOfInterestField, 'id', DashboardFilters::DASHBOARD_MEMBERS_LIST_GROUP_ID), $topicsVocabulary));
    $groupMembersByInterestChart = $this->dashboardBuilder->chartPie($this->t('Members by topics of interest'), $groupMembersByInterestData, '');

    $section3Build = [
      $this->dashboardBuilder->columns([
        $groupMembersByExpertiseChart,
        $groupMembersByInterestChart,
      ], 2)
    ];

    // Group events statistics.
    $eventsTitle = 'Events';
    $eventsType = 'group-group_node-event';

    // Group events.
    $groupTotalEventsData = $this->groupStatistics->getNumberOfContentInGivenPeriod($group, $eventsType);
    $groupTotalEvents = $this->dashboardBuilder->numberAndLink($this->t('Total events'), $groupTotalEventsData, '');

    // Group events created in past days.
    $groupEventsCreatedInPastDaysData = $this->groupStatistics->getNumberOfContentInGivenPeriod($group, $eventsType, $lastDaysLimit);
    $groupEventsCreatedInPastDays = $this->dashboardBuilder->numberAndLink($this->t('New events - past @limit days', ['@limit' => $lastDaysLimit]), $groupEventsCreatedInPastDaysData, '');

    // Group events by type chart.
    $typeField = 'field_vocab_event_type';
    $groupEventsByTypeData = json_encode($this->dashboardHelper->transformLabelCountToNameAndY($this->groupStatistics->getGroupNodesOfGroupByTerm($group, $eventsType, $typeField)));
    $groupEventsByType = $this->dashboardBuilder->chartPie($this->t('Events by type'), $groupEventsByTypeData, '');

    // Group events by topic chart.
    $topicField = 'field_vocab_topics';
    $groupEventsByTopicData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->groupStatistics->getGroupNodesOfGroupByTerm($group, $eventsType, $topicField), $topicsVocabulary));
    $groupEventsByTopic = $this->dashboardBuilder->chartPie($this->t('Events by topic'), $groupEventsByTopicData, '');

    $eventsContent = [
      $this->dashboardBuilder->columns([
        $groupTotalEvents,
        $groupEventsCreatedInPastDays,
        $groupEventsByType,
        $groupEventsByTopic,
      ], 2)
    ];

    // Section 4.
    $section4Build = [$this->dashboardBuilder->dashboardSection($eventsTitle, '', $eventsContent, 'events')];

    // Group discussions statistics.
    $discussionsTitle = 'Forum discussions';
    $discussionType = 'group-group_node-discussion';

    $discussionsButton = $this->dashboardBuilder->buttonToRoute($this->t('List all'), 'eic_overviews.groups.overview_page.discussions', 'group', $group->id());

    $groupTotalDiscussionsData = $this->groupStatistics->getNumberOfContentInGivenPeriod($group, $discussionType);
    $groupTotalDiscussions = $this->dashboardBuilder->numberAndLink($this->t('Total discussions'), $groupTotalDiscussionsData, '');

    $groupDiscussionsPastDaysData = $this->groupStatistics->getNumberOfContentInGivenPeriod($group, $discussionType, $lastDaysLimit);
    $groupDiscussionsPastDays = $this->dashboardBuilder->numberAndLink($this->t('New discussions in last @limit days', ['@limit' => $lastDaysLimit]), $groupDiscussionsPastDaysData, '');

    $discussionsContent = [
      $this->dashboardBuilder->columns([
        $groupTotalDiscussions,
        $groupDiscussionsPastDays,
      ], 2)
    ];

    // Section 5.
    $section5Build = [$this->dashboardBuilder->dashboardSection($discussionsTitle, $discussionsButton, $discussionsContent, 'discussions')];

    // Group files statistics.
    $filesTitle = 'Files';
    $fileType = 'group-group_node-document';

    $filesButton = $this->dashboardBuilder->buttonToRoute($this->t('List all'), 'eic_overviews.groups.overview_page.files', 'group', $group->id());

    $groupTotalFilesData = $this->groupStatistics->getNumberOfContentInGivenPeriod($group, $fileType);
    $groupTotalFiles = $this->dashboardBuilder->numberAndLink($this->t('Total files'), $groupTotalFilesData, '');

    $groupFilesPastDaysData = $this->groupStatistics->getNumberOfContentInGivenPeriod($group, $fileType, $lastDaysLimit);
    $groupFilesPastDays = $this->dashboardBuilder->numberAndLink($this->t('New files in last @limit days', ['@limit' => $lastDaysLimit]), $groupFilesPastDaysData, '');

    $filesContent = [
      $this->dashboardBuilder->columns([
        $groupTotalFiles,
        $groupFilesPastDays,
      ], 2)
    ];

    // Section 6
    $section6Build = [$this->dashboardBuilder->dashboardSection($filesTitle, $filesButton, $filesContent, 'files')];

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'ecl-container',
          'ecl-u-mv-xl',
        ],
      ],
      'content' => [
        $section1Build,
        $section2Build,
        $section3Build,
        $section4Build,
        $section5Build,
        $section6Build,
      ],
    ];

    return $build;
  }

}
