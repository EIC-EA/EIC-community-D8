<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardCacheManager;
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
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardCacheManager
   */
  protected DashboardCacheManager $dashboardCacheManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface   $dashboardHelper,
    DashboardCacheManager $dashboardCacheManager,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->dashboardCacheManager = $dashboardCacheManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.cache_manager'),
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
      '#content' => [
        $this->dashboardBuilder->ctaCard('Documents', $this->dashboardHelper->getRoutingUrl('eic_dashboards.content.documents'), 'content-documents', 'content'),
        $this->dashboardBuilder->ctaCard('Discussions', $this->dashboardHelper->getRoutingUrl('eic_dashboards.content.discussions'), 'content-discussions', 'content'),
        $this->dashboardBuilder->ctaCard('Stories', $this->dashboardHelper->getRoutingUrl('eic_dashboards.content.stories'), 'content-stories', 'content'),
      ],
      '#listings' => [
        $this->dashboardBuilder->ctaCard('Members list', $this->dashboardHelper->getRoutingUrl('view.dashboard_members_list.page'), 'list-members', 'list'),
        $this->dashboardBuilder->ctaCard('Organisations list', $this->dashboardHelper->getRoutingUrl('view.dashboard_organisations_list.page'), 'list-organisations', 'list'),
        $this->dashboardBuilder->ctaCard('Projects list', $this->dashboardHelper->getRoutingUrl('view.dashboard_projects_list.page'), 'list-projects', 'list'),
        $this->dashboardBuilder->ctaCard('Content list', $this->dashboardHelper->getRoutingUrl('view.dashboard_content_list.page'), 'list-content', 'list'),
        $this->dashboardBuilder->ctaCard($this->dashboardHelper->getRoutingTitle('eic_dashboards.listings.activity_report'), $this->dashboardHelper->getRoutingUrl('eic_dashboards.listings.activity_report'), 'activity-report', 'list'),
      ],
    ];
    return $build;
  }

  public function invalidateCaches() {
    $this->dashboardCacheManager->invalidateAllCaches();
    $this->messenger()->addStatus($this->t("Caches have been invalidated."));
    return $this->redirect('eic_dashboards.homepage');
  }
}
