<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eic_dashboards\Services\DashboardsBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardsHelperInterface;

/**
 * Provides route responses for the eic_dashboards module.
 */
class DashboardsController extends ControllerBase
{

  /**
   * The dashboards builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardsBuilderInterface
   */
  protected DashboardsBuilderInterface $dashboardsBuilder;

    /**
     * The dashboard helper service.
     *
     * @var \Drupal\eic_dashboards\Services\DashboardsHelperinterface
     */
    protected DashboardsHelperInterface $dashboardsHelper;


  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardsBuilderInterface $dashboards_builder,
    DashboardsHelperInterface $dashboardsHelper,
  )
  {
    $this->dashboardsBuilder = $dashboards_builder;
    $this->dashboardsHelper = $dashboardsHelper;
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

    $test = $this->dashboardsHelper;

    // Build the render array.
    $build['content'] = [
      '#theme' => 'dashboards_homepage',
      '#title' => $title,
      '#dashboards' => [
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.members_dashboard'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.members_dashboard'), 'dashboard-members', 'dashboard'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.organisations_dashboard'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.organisations_dashboard'), 'dashboard-organisations', 'dashboard'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.projects_dashboard'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.projects_dashboard'), 'dashboard-projects', 'dashboard'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.content_dashboard'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.content_dashboard'), 'dashboard-content', 'dashboard'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.events_dashboard'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.events_dashboard'), 'dashboard-events', 'dashboard'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.groups_dashboard'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.groups_dashboard'), 'dashboard-groups', 'dashboard'),
      ],
      '#listings' => [
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.members_list'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.groups_dashboard'), 'list-members', 'list'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.organisations_list'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.groups_dashboard'), 'list-organisations', 'list'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.projects_list'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.projects_list'), 'list-projects', 'list'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.content_list'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.content_list'), 'list-content', 'list'),
        $this->dashboardsBuilder->ctaCard($this->dashboardsHelper->getRoutingTitle('eic_dashboards.activity_report'), $this->dashboardsHelper->getRoutingUrl('eic_dashboards.activity_report'), 'activity-report', 'list'),
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
