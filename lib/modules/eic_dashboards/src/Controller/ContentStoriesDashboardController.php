<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\ContentStatisticsInterface;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays statistics for stories.
 */
class ContentStoriesDashboardController extends ControllerBase {

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
   * @var \Drupal\eic_dashboards\Services\ContentStatisticsInterface
   */
  protected ContentStatisticsInterface $contentStatistics;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface   $dashboardHelper,
    ContentStatisticsInterface $contentStatistics,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->contentStatistics = $contentStatistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.content_statistics'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function page(): array {
    // Specify current bundle, title and link.
    $bundle = 'story';
    $title = 'Stories';
    $link = $this->dashboardBuilder->buttonToView('view.dashboard_content_list.page', 'type', '', 'List all');

    // Number of nodes.
    $numberOfNodesData = $this->contentStatistics->getNumberOfBundleNodes($bundle);
    $numberOfNodes = $this->dashboardBuilder->numberAndLink($this->t('Total stories'), $numberOfNodesData, '');

    // Number of nodes created in past 30 days.
    $lastDaysLimit = 30;
    $numberOfNodesPastDaysData = $this->contentStatistics->getNumberOfBundleNodesPastDays($bundle, $lastDaysLimit);
    $numberOfNodesPastDays = $this->dashboardBuilder->numberAndLink($this->t('Stories - last @days days', ['@days' => $lastDaysLimit]), $numberOfNodesPastDaysData, '');

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $numberOfNodes,
        $numberOfNodesPastDays,
      ], 3),
    ];

    // Nodes grouped by program type chart.
    $nodesByProgramTypeData = json_encode($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_vocab_program_type', 'pie'));
    $nodesByProgramType = $this->dashboardBuilder->chartPie($this->t('Stories by program type'), $nodesByProgramTypeData, '');

    // Nodes grouped by type chart.
    $nodesByTypeData = json_encode($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_vocab_story_type', 'pie'));
    $nodesByType = $this->dashboardBuilder->chartPie($this->t('Stories by type'), $nodesByTypeData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $nodesByProgramType,
        $nodesByType,
      ], 2),
    ];

    // Nodes by topic.
    $topicsVocabulary = 'topics';
    $nodesByTopicData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformNameYToCategoriesSeries($this->dashboardHelper->transformTermTreeCountsForChart($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_vocab_topics', 'column'), $topicsVocabulary)));
    $nodesByTopicChart = $this->dashboardBuilder->chartColumn($this->t('Stories by topic'), $nodesByTopicData, true);

    // Section 3.
    $section3Build = [
      $this->dashboardBuilder->columns([
        $nodesByTopicChart,
      ], 1),
    ];

    // Last 10 nodes list table.
    $latestNodesLimit = 10;
    $latestNodesMetricsData = $this->contentStatistics->getLastStoriesMetrics($latestNodesLimit);
    $latestNodesMetrics = $this->dashboardBuilder->table(
      $latestNodesMetricsData['header'],
      $latestNodesMetricsData['rows'],
      'stories-table js-stories-table'
    );

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $latestNodesMetrics,
      ], 1),
    ];

    // Most viewed nodes.
    $mostViewedNodes = $this->dashboardBuilder->titleLinkList($this->t('Most viewed stories - all time'), '', $this->contentStatistics->getMostViewedNodesOfBundle($bundle));

    // Section 5.
    $section5Build = [
      $this->dashboardBuilder->columns([
        $mostViewedNodes,
      ], 2),
    ];

    $content = [
      $section1Build,
      $section2Build,
      $section3Build,
      $section4Build,
      $section5Build,
    ];

    return [$this->dashboardBuilder->dashboardSection($title, $link, $content, 'stories', FALSE)];
  }
}
