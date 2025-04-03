<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\ContentStatisticsInterface;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the eic_dashboards module.
 */
class StoriesContentDashboardController extends ControllerBase
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
    // Number of nodes.
    $numberOfNodesData = $this->contentStatistics->getNumberOfBundleNodes('story');
    $numberOfNodesLink = $this->dashboardBuilder->buttonToView('view.dashboard_content_list.page_1', '', '', $this->t
    ('Content list'));
    $numberOfNodes = $this->dashboardBuilder->numberAndLink($this->t('Total stories'), $numberOfNodesData,
      $numberOfNodesLink);

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $numberOfNodes,
      ], 3),
    ];

    $build = [
      'content' => [
        $section1Build,
      ],
    ];

    return $build;
  }
}
