<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eic_dashboards\Services\DashboardsBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the eic_dashboards module.
 */
class DashboardsController extends ControllerBase
{

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardsBuilderInterface
   */
  protected DashboardsBuilderInterface $dashboardsBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardsBuilderInterface $dashboards_builder,
  )
  {
    $this->dashboardsBuilder = $dashboards_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('eic_dashboards.builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function dashboardsHomepage(): array
  {
    // Get the title from the route definition.
    $title = \Drupal::routeMatch()->getRouteObject()->getDefault('_title');

    // Build the render array.
    // TODO: Create a helper functions for retrieving information from dashboards/list routes.
    $build['content'] = [
      '#theme' => 'dashboards_homepage',
      '#title' => $title,
      '#dashboards' => [
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.members_dashboard')->getDefault('_title'), Url::fromRoute('eic_dashboards.members_dashboard')->toString(), '', 'dashboard'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.organisations_dashboard')->getDefault('_title'), Url::fromRoute('eic_dashboards.organisations_dashboard')->toString(), '', 'dashboard'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.projects_dashboard')->getDefault('_title'), Url::fromRoute('eic_dashboards.projects_dashboard')->toString(), '', 'dashboard'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.content_dashboard')->getDefault('_title'), Url::fromRoute('eic_dashboards.content_dashboard')->toString(), '', 'dashboard'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.events_dashboard')->getDefault('_title'), Url::fromRoute('eic_dashboards.events_dashboard')->toString(), '', 'dashboard'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.groups_dashboard')->getDefault('_title'), Url::fromRoute('eic_dashboards.groups_dashboard')->toString(), '', 'dashboard'),
      ],
      '#listings' => [
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.members_list')->getDefault('_title'), Url::fromRoute('eic_dashboards.members_list')->toString(), '', 'list'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.organisations_list')->getDefault('_title'), Url::fromRoute('eic_dashboards.organisations_list')->toString(), '', 'list'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.projects_list')->getDefault('_title'), Url::fromRoute('eic_dashboards.projects_list')->toString(), '', 'list'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.content_list')->getDefault('_title'), Url::fromRoute('eic_dashboards.content_list')->toString(), '', 'list'),
        $this->dashboardsBuilder->ctaCard(\Drupal::service('router.route_provider')->getRouteByName('eic_dashboards.activity_report')->getDefault('_title'), Url::fromRoute('eic_dashboards.activity_report')->toString(), '', 'list'),
      ],
    ];
    // Disable cache to always get fresh content.
    $build['#cache'] = ['max-age' => 0];

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
