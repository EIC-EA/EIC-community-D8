<?php

namespace Drupal\eic_dashboards\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_dashboards\Services\DashboardBuilderInterface;
use Drupal\eic_dashboards\Services\DashboardHelperInterface;
use Drupal\eic_dashboards\Services\GroupStatisticsInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains functions that create individual group dashboard.
 */
class IndividualGroupDashboardController extends ControllerBase {

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardBuilderinterface
   */
  protected DashboardBuilderInterface $dashboardBuilder;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardHelperinterface
   */
  protected DashboardHelperInterface $dashboardHelper;

  /**
   * The group statistics service.
   *
   * @var \Drupal\eic_dashboards\Services\GroupStatisticsInterface
   */
  protected GroupStatisticsInterface $groupStatistics;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    DashboardBuilderInterface $dashboardBuilder,
    DashboardHelperInterface $dashboardHelper,
    GroupStatisticsInterface $groupStatistics,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->dashboardBuilder = $dashboardBuilder;
    $this->dashboardHelper = $dashboardHelper;
    $this->groupStatistics = $groupStatistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_dashboards.builder'),
      $container->get('eic_dashboards.helper'),
      $container->get('eic_dashboards.group_statistics'),
    );
  }

  /**
   * Checks access to the individual group dashboard pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden
   *   The access result.
   */
  public function access(AccountInterface $account, GroupInterface $group) {
    $groupType = $group->getGroupType();

    if ($groupType->id() !== 'group') {
      return AccessResult::forbidden()
        ->addCacheableDependency($group);
    }

    // TODO: Delete when current development is over.
    if ($account->id() === '1') {
      return AccessResult::allowed()
        ->addCacheableDependency($account)
        ->cachePerPermissions();
    }

    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $groupMembership = $group->getMember($account);

    if ($groupMembership instanceof GroupMembership) {
      foreach ($groupMembership->getRoles() as $group_role) {
        if ($group_role->id() === 'group-admin' or $group_role->id() === 'group-owner') {
          return AccessResult::allowed()
            ->addCacheableDependency($group)
            ->addCacheableDependency($user)
            ->cachePerPermissions();
        }
      }
    }

    return AccessResult::forbidden()
      ->cachePerUser()
      ->addCacheableDependency($user);
  }

  /**
   * Group dashboard page.
   */
  public function page(GroupInterface $group): array {
    // Define past days limit.
    $lastDaysLimit = 30;

    // Group members.
    $groupMembersData = $this->groupStatistics->getGroupMembers($group);
    $groupMembers = $this->dashboardBuilder->numberAndLink($this->t('Total members'), $groupMembersData, '');

    $groupMembersJoinedInPastDaysData = $this->groupStatistics->getGroupMembersRegisteredPastDays($group,
      $lastDaysLimit);
    $groupMembersJoinedInPastDays = $this->dashboardBuilder->numberAndLink($this->t('Joined - past @limit days', ['@limit' => $lastDaysLimit]), $groupMembersJoinedInPastDaysData, '');

    $groupMembersLoggedInPastDaysData = $this->groupStatistics->getGroupMembersLoggedPastDays($group, $lastDaysLimit);
    $groupMembersLoggedInPastDays = $this->dashboardBuilder->numberAndLink($this->t('Logged in - past @limit days', ['@limit' => $lastDaysLimit]), $groupMembersLoggedInPastDaysData, '');

    // Section 1.
    $section1Build = [
      $this->dashboardBuilder->columns([
        $groupMembers,
        $groupMembersJoinedInPastDays,
        $groupMembersLoggedInPastDays,
      ], 3),
    ];

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'ecl-container',
          'ecl-u-mv-xl',
        ],
      ],
      'content' => [
        $section1Build,
      ],
    ];

    return $build;
  }

}
