<?php

namespace Drupal\eic_stakeholder\Routing;

use Drupal\eic_stakeholder\Entity\StakeholderType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for eic_stakeholder group content.
 */
class GroupStakeholderRouteProvider {

  /**
   * Provides the shared collection route for group node plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (StakeholderType::loadMultiple() as $name => $stakeholder_type) {
      $plugin_id = "group_stakeholder:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no stakeholder types yet, we cannot have any plugin IDs and should
    // therefore exit early because we cannot have any routes for them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_content.eic_stakeholder_relate_page'] = new Route('group/{group}/stakeholder/add');
    $routes['entity.group_content.eic_stakeholder_relate_page']
      ->setDefaults([
        '_title' => 'Add existing content',
        '_controller' => '\Drupal\eic_stakeholder\Controller\GroupStakeholderController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.eic_stakeholder_add_page'] = new Route('group/{group}/stakeholder/create');
    $routes['entity.group_content.eic_stakeholder_add_page']
      ->setDefaults([
        '_title' => 'Add new content',
        '_controller' => '\Drupal\eic_stakeholder\Controller\GroupStakeholderController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
