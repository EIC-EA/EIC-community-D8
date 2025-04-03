<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;

/**
 * Provides route responses for the eic_dashboards module.
 */
class DashboardsController extends ControllerBase
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
    DashboardHelperInterface  $dashboardHelper,
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
  public function dashboardsHomepage(): array
  {
    // Get the title from the route definition.
    $title = \Drupal::routeMatch()->getRouteObject()->getDefault('_title');

    $test = $this->dashboardHelper;

    // Build the render array.
    $build['content'] = [
      '#theme' => 'dashboards_homepage',
      '#title' => $title,
      '#dashboards' => [
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.members_dashboard'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.members_dashboard'), 'dashboard-members', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.organisations_dashboard'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.organisations_dashboard'), 'dashboard-organisations', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.projects_dashboard'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.projects_dashboard'), 'dashboard-projects', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.content_dashboard'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.content_dashboard'), 'dashboard-content', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.events_dashboard'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.events_dashboard'), 'dashboard-events', 'dashboard'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.groups_dashboard'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.groups_dashboard'), 'dashboard-groups', 'dashboard'),
      ],
      '#listings' => [
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.members_list'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.groups_dashboard'), 'list-members', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.organisations_list'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.groups_dashboard'), 'list-organisations', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.projects_list'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.projects_list'), 'list-projects', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.content_list'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.content_list'), 'list-content', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.activity_report'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.activity_report'), 'activity-report', 'list'),
      ]
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function ctaCard($title, $link, $icon, $variant): array
  {
    return [
      '#theme' => 'cta_card',
      '#title' => $title,
      '#link' => $link,
      "#icon" => $icon,
      "#variant" => $variant,
    ];
  }

}
