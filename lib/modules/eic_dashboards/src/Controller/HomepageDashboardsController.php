<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;

/**
 * Provides route responses for the eic_dashboards module.
 */
class HomepageDashboardsController extends ControllerBase {

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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function dashboardsHomepage(): array {
    // Get the title from the route definition.
    $title = \Drupal::routeMatch()->getRouteObject()->getDefault('_title');

    // Build the render array.
    $build['content'] = [
      '#theme' => 'dashboards_homepage',
      '#title' => $title,
      '#dashboards' => [
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.members'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.members'), 'dashboard-members', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.group.organisations'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.group.organisations'), 'dashboard-organisations', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.group.projects'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.group.projects'), 'dashboard-projects', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.group.events'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.group.events'), 'dashboard-events', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.group.groups'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.group.groups'), 'dashboard-groups', 'dashboard'),
      ],
      '#listings' => [
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.listings.members'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.listings.members'), 'list-members', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.listings.organisations'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.listings.organisations'), 'list-organisations', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.listings.projects'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.listings.projects'), 'list-projects', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.listings.content'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.listings.content'), 'list-content', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.listings.activity_report'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.listings.activity_report'), 'activity-report', 'list'),
      ]
    ];
    return $build;
  }
}
