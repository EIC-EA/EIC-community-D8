<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\PlatformStatisticsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;

/**
 * Provides route responses for the eic_dashboards module.
 */
class MembersDashboardController extends ControllerBase
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
   * The platform statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\PlatformStatisticsInterface
   */
  protected PlatformStatisticsInterface $platformStatistics;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface   $dashboardHelper,
    PlatformStatisticsInterface $platformStatistics,
  )
  {
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->platformStatistics = $platformStatistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.platform_statistics'),
    );
  }

  /**
   * Members overview.
   */
  public function membersOverview(): array {
    // Platform members.
    $platformMembersData = $this->platformStatistics->getTotalPlatformMembers();
    $platformMembersLink = $this->dashboardBuilder->buttonToView('view.dashboard_members_list.page', '', '', $this->t('Members list'));
    $platformMembers = $this->dashboardBuilder->numberAndLink($this->t('Platform members'), $platformMembersData, $platformMembersLink);

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $platformMembers,
      ], 3),
    ];

    // Members by organisation type.
    $membersByOrganizationTypeData = json_encode($this->dashboardHelper->transformLabelCountToNameAndY($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_user_type', 'id')));
    $a = $membersByOrganizationTypeData;
    $membersByOrganizationTypeChart = $this->dashboardBuilder->chartPie($this->t('Members by organisation type'), $membersByOrganizationTypeData, '');
//    $membersByOrganizationTypeMenuData = $this->dashboardHelper->prepareDataForJumpMenu($this->platformStatistics->getMembersPerTaxonomyTerm('field_vocab_user_type', DashboardFilters::DASHBOARD_MEMBERS_LIST_ORGANIZATION_TYPE), 'view.members_list.page', [], [DashboardFilters::DASHBOARD_MEMBERS_LIST_ORGANIZATION_TYPE]);
//    $membersByOrganizationTypeMenu = $this->dashboardBuilder->jumpMenu($this->t('List members of type'), $this->t('Choose organisation type'), $membersByOrganizationTypeMenuData);
//    $membersByOrganizationType = $this->dashboardBuilder->chartWithMenu($membersByOrganizationTypeChart, $membersByOrganizationTypeMenu);
    $membersByOrganizationType = $this->dashboardBuilder->chartWithMenu($membersByOrganizationTypeChart, '');

    // Section 3.
    $section3Build = $this->dashboardBuilder->columns([
      $membersByOrganizationType,
    ], 2);


    $build = [
      'content' => [
        $section1Build,
        $section3Build,
      ],
    ];

    return $build;
  }
}
