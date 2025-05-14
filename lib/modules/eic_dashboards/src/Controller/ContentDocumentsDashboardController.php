<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;
use Drupal\eic_dashboards\Services\ContentStatisticsInterface;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardCumulativeService;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays statistics for documents.
 */
class ContentDocumentsDashboardController extends ControllerBase {

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
    DashboardHelperInterface   $dashboardHelper,
    ContentStatisticsInterface $contentStatistics,
    DashboardCumulativeService $dashboardCumulativeService,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->contentStatistics = $contentStatistics;
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
      $container->get('eic_dashboards.content_statistics'),
      $container->get('eic_dashboards.cumulative'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function page(): array {
    // Specify current bundle, title and link.
    $bundle = 'document';
    $title = 'Documents';
    $documentTypes = ['event-group_node-document', 'group-group_node-document', 'organisation-group_node-document'];
    $link = $this->dashboardBuilder->buttonToView('view.dashboard_content_list.page', 'type', $documentTypes, 'List all');

    // Number of nodes.
    $numberOfNodesData = $this->contentStatistics->getNumberOfBundleNodes($bundle);
    $numberOfNodes = $this->dashboardBuilder->numberAndLink($this->t('Total documents'), $numberOfNodesData, '');

    // Number of nodes created in past 30 days.
    $numberOfNodesPastDaysData = $this->contentStatistics->getNumberOfBundleNodesPastDays($bundle);
    $numberOfNodesPastDays = $this->dashboardBuilder->numberAndLink($this->t('Documents - last 30 days'),
      $numberOfNodesPastDaysData, '');

    $nodesStats = [
      $this->dashboardBuilder->columns([
        $numberOfNodes,
        $numberOfNodesPastDays,
      ], 3),
    ];

    // Nodes evolution.
    $nodesCumulativeStats = $this->dashboardCumulativeService->getCumulativeStatsPerDashboardType(DashboardsDatabase::DOCUMENTS_DASHBOARD_TYPE);
    $nodesData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($nodesCumulativeStats));
    $nodesEvolution = $this->dashboardBuilder->chartLine($this->t('Documents - evolution over time'), $nodesData);

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $nodesStats,
        $nodesEvolution,
      ], 1),
    ];

    // Nodes grouped by type chart.
    $nodesByTypeData = json_encode($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_document_type', 'pie'));
    $nodesByType = $this->dashboardBuilder->chartPie($this->t('Documents by type'), $nodesByTypeData, '');

    // Nodes grouped by topic chart.
    $topicsVocabulary = 'topics';
    $nodesByTopicData = json_encode($this->dashboardHelper->transformTermTreeCountsForChart($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_vocab_topics', 'column'), $topicsVocabulary));
    $nodesByTopic = $this->dashboardBuilder->chartPie($this->t('Documents by topic'), $nodesByTopicData, '');

    // Section 2.
    $section2Build = [
      $this->dashboardBuilder->columns([
        $nodesByType,
        $nodesByTopic,
      ], 2),
    ];

    // Most viewed nodes.
    $mostViewedNodes = $this->dashboardBuilder->titleLinkList($this->t('Most viewed documents'), '', $this->contentStatistics->getMostViewedNodesOfBundle($bundle));

    // Most downloaded files.
    $mostDownloadedFiles = $this->dashboardBuilder->titleLinkList($this->t('Most downloaded documents'), '',
      $this->contentStatistics->getMostDownloadedFilesOfBundle($bundle));

    // Section 3.
    $section3Build = [
      $this->dashboardBuilder->columns([
        $mostViewedNodes,
        $mostDownloadedFiles,
      ], 2),
    ];

    // Top terms used.
    $topTermsLimit = 10;
    $topTerms = $this->dashboardHelper->jsonEncodeCategoriesSeries
    ($this->dashboardHelper->transformIdCountToCategoriesSeries($this->contentStatistics->getNodesOfBundlePerTerm($bundle, 'field_tags', 'column', $topTermsLimit)));
    $topTermsChart = $this->dashboardBuilder->chartColumn( $this->t('Top @limit document tags', ['@limit' => $topTermsLimit]), $topTerms, true);

    // Latest nodes.
    $latestNodes = $this->dashboardBuilder->titleLinkList($this->t('Latest documents'), '', $this->contentStatistics->getNodesOfBundleInGivenPeriod($bundle, '', ''));

    // Section 4.
    $section4Build = [
      $this->dashboardBuilder->columns([
        $topTermsChart,
        $latestNodes,
      ], 2),
    ];

    $content = [
      $section1Build,
      $section2Build,
      $section3Build,
      $section4Build,
    ];

    return [$this->dashboardBuilder->dashboardSection($title, $link, $content, 'files', FALSE)];
  }
}
