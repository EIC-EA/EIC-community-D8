<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;

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
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface   $dashboardHelper,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function page(): array {
    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        'Stories content & charts.',
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
