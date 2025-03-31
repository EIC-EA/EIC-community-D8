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
   * Members overview.
   */
  public function membersOverview(): array {
    // Platform members.
    $platformMembersData = $this->platformStatistics->getTotalPlatformMembers();
    $platformMembersLink = $this->dashboardBuilder->buttonToView('view.members_list.page', '', '', $this->t('Members list'));
    $platformMembers = $this->dashboardBuilder->numberAndLink($this->t('Platform members'), $platformMembersData, $platformMembersLink);
//
//    // Joined in the past 30 days.
//    $membersCreatedPast30DaysData = $this->platformStatistics->getPlatformMembersRegisteredPast30Days();
//    $membersCreatedPast30DaysLink = $this->dashboardBuilder->buttonToView('view.members_list.page', 'registered_from', gmdate("Y-m-d", strtotime('-30 days')), $this->t('Members list'));
//    $membersCreatedPast30Days = $this->dashboardBuilder->numberAndLink($this->t('Joined in the past 30 days'), $membersCreatedPast30DaysData, $membersCreatedPast30DaysLink);
//
//    // Recently logged in.
//    $membersLoggedPast7DaysData = $this->platformStatistics->getPlatformMembersLoggedPast7Days();
//    $membersLoggedPast7DaysLink = $this->dashboardBuilder->buttonToView('view.members_list.page', 'last_access_from', gmdate("Y-m-d", strtotime('-7 days')), $this->t('Members list'));
//    $membersLoggedPast7Days = $this->dashboardBuilder->numberAndLink($this->t('Recently logged in'), $membersLoggedPast7DaysData, $membersLoggedPast7DaysLink);
//
//    // Members profile visibility.
//    $membersShownListingData = $this->platformStatistics->getPlatformMembersShownInListing();
//    $membersHiddenListingData = $this->platformStatistics->getPlatformMembersHiddenFromListing();
//    $membersHiddenListingLink = $this->dashboardBuilder->buttonToView('view.members_list.page', 'field_appear_on_members_listing_value', '0', $this->t('Members list'));
//    $membersHiddenListing = $this->dashboardBuilder->numberAndLink($this->t('Hidden from members listing'), $membersHiddenListingData, $membersHiddenListingLink);
//    $membersProfileVisibilityData = json_encode(
//      [
//        ['name' => $this->t('Visible profile'), 'y' => $membersShownListingData],
//        ['name' => $this->t('Hidden profile'), 'y' => $membersHiddenListingData],
//      ], JSON_NUMERIC_CHECK);
//    $membersProfileVisibility = $this->dashboardBuilder->chartPie($this->t('Members profile visibility'), $membersProfileVisibilityData, 'sm');
//
//    // Members evolution.
//    $membersCumulativeStats = $this->platformStatistics->getCumulativeStatsForBundle('platform_member');
//    $membersData = $this->dashboardHelper->jsonEncodeCategoriesSeries($this->dashboardHelper->transformIdCountToCategoriesSeries($membersCumulativeStats));
//    $membersEvolution = $this->dashboardBuilder->chartLine($this->t('Members - evolution over time'), $membersData);
//
//    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $platformMembers,
//        $membersCreatedPast30Days,
//        $membersLoggedPast7Days,
//        $membersHiddenListing,
//        $membersProfileVisibility,
      ], 3),
//      $this->dashboardBuilder->columns([$membersEvolution], 1),
    ];

    $build = [
      'content' => [
        $section1Build,
      ],
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
