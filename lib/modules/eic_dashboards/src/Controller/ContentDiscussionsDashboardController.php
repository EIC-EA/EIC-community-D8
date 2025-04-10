<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\ContentStatisticsInterface;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays statistics for discussions.
 */
class ContentDiscussionsDashboardController extends ControllerBase
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
    // Specify current bundle.
    $bundle = 'discussion';

    // Number of nodes.
    $numberOfNodesData = $this->contentStatistics->getNumberOfBundleNodes($bundle);
    $numberOfNodes = $this->dashboardBuilder->numberAndLink($this->t('Total discussions'), $numberOfNodesData, '');

    // Number of nodes created in past 30 days.
    $numberOfNodesPast30DaysData = $this->contentStatistics->getNumberOfBundleNodesPastDays($bundle);
    $numberOfNodesPast30Days = $this->dashboardBuilder->numberAndLink($this->t('New discussions in the last 30 days'), $numberOfNodesPast30DaysData, '');

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $numberOfNodes,
        $numberOfNodesPast30Days,
      ], 3),
    ];

    // Nodes grouped by type chart.
    $nodesByTypeData = json_encode($this->contentStatistics->getNodesOfBundlePerValue($bundle, 'field_discussion_type', 'pie'));
    $nodesByType = $this->dashboardBuilder->chartPie($this->t('Discussions by type'), $nodesByTypeData, '');

    // Nodes grouped by topic chart.
    // TODO: Clarify which levels of vocabularies should be displayed.
    $topicTermId = 506;
    $nodesByTopicData = json_encode($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_vocab_topics', 'pie', $topicTermId));
    $nodesByTopic = $this->dashboardBuilder->chartPie($this->t('Discussions by topic'), $nodesByTopicData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $nodesByType,
        $nodesByTopic,
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
