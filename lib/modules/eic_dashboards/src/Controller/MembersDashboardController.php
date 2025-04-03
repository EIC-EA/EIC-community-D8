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
   * Members overview.
   */
  public function membersOverview(): array {
    // Platform members.
    $platformMembersData = $this->platformStatistics->getTotalPlatformMembers();
    $platformMembersLink = $this->dashboardBuilder->buttonToView('view.members_list.page', '', '', $this->t('Members list'));
    $platformMembers = $this->dashboardBuilder->numberAndLink($this->t('Platform members'), $platformMembersData, $platformMembersLink);

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $platformMembers,
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
