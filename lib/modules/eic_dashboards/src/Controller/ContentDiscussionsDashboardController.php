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
class ContentDiscussionsDashboardController extends ControllerBase {

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
    $bundle = 'discussion';
    $title = 'Forum discussions';
    $link = '';

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
    $topicsVocabulary = 'topics';
    $nodesByTopicData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_vocab_topics', 'column'), $topicsVocabulary));
    $nodesByTopic = $this->dashboardBuilder->chartPie($this->t('Discussions by topic'), $nodesByTopicData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $nodesByType,
        $nodesByTopic,
      ], 2),
    ];

    // Top terms used.
    $topTermsLimit = 10;
    $topTerms = $this->dashboardHelper->jsonEncodeCategoriesSeries
    ($this->dashboardHelper->transformIdCountToCategoriesSeries($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_tags', 'column', $topTermsLimit)));
    $topTermsChart = $this->dashboardBuilder->chartColumn( $this->t('Top @limit discussion tags', ['@limit' => $topTermsLimit]), $topTerms, true);

    // Top groups by number of nodes.
    $nodeType = 'group-group_node-discussion';
    $topGroupsLimit = 10;
    $topGroupsByNumberOfNodes = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($this->contentStatistics->getGroupsByNumberOfBundle($nodeType, '', $topGroupsLimit)));
    $topGroupsByNumberOfNodesChart = $this->dashboardBuilder->chartColumn( $this->t('Top @limit groups by number of discussions', ['@limit' => $topGroupsLimit]), $topGroupsByNumberOfNodes, true);

    // Section 3.
    $section3Build = [
      $this->dashboardBuilder->columns([
        $topTermsChart,
        $topGroupsByNumberOfNodesChart,
      ], 2),
    ];

    // Most viewed nodes.
    $mostViewedNodes = $this->dashboardBuilder->titleLinkList($this->t('Most viewed discussions'), '', $this->contentStatistics->getMostViewedNodesOfBundle($bundle));

    // Most commented nodes.
    $mostCommentedNodes = $this->dashboardBuilder->titleLinkList($this->t('Most commented discussions'), '', $this->contentStatistics->getMostCommentedNodesOfBundle($bundle));

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $mostViewedNodes,
        $mostCommentedNodes,
      ], 2),
    ];

    // Latest nodes.
    $latestNodes = $this->dashboardBuilder->titleLinkList($this->t('Latest discussions'), '', $this->contentStatistics->getNodesOfBundleInGivenPeriod($bundle, '', ''));

    // Section 5.
    $section5Build = [
      $this->dashboardBuilder->columns([
        $latestNodes,
        '',
      ], 2),
    ];

    $content = [
      $section1Build,
      $section2Build,
      $section3Build,
      $section4Build,
      $section5Build,
    ];

    return [$this->dashboardBuilder->dashboardSection($title, $link, $content, 'discussions', FALSE)];
  }
}
