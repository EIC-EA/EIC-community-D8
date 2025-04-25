<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
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
    $groupType = 'event';

    // Define number of past days.
    $lastDaysLimit = 90;

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

    // Groups by type chart.
    $typeField = 'field_vocab_event_type';
    $groupsByTypeData = json_encode($this->groupStatistics->getGroupsByTerm($groupType, $typeField));
    $groupsByType = $this->dashboardBuilder->chartPie($this->t('Events by type'), $groupsByTypeData, '');

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
        '',
        $groupsByTopic,
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
