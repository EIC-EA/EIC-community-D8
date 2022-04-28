<?php

namespace Drupal\eic_groups\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\eic_groups\ForbiddenOrphanContentTypes;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter some entity add routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    foreach (ForbiddenOrphanContentTypes::FORBIDDEN_ENTITY_ROUTES as $route_name => $definition) {
      $add_route = $collection->get($route_name);
      if (!$add_route instanceof Route) {
        continue;
      }

      $add_route->setRequirement('_orphan_group_content_access_check', 'TRUE');
    }

    $denied_routes_archived_group = [
      'entity.group.new_request',
      'entity.group_content.create_form',
      'entity.group_content.add_form',
      'entity.group.leave',
      'entity.group.new_request',
      'entity.group.edit_form',
      'view.eic_group_members.page_group_members',
      'ginvite.invitation.bulk',
      'entity.group.join',
      'entity.group.group_request_membership',
    ];

    foreach ($denied_routes_archived_group as $route_name) {
      $route = $collection->get($route_name);

      if (!$route instanceof Route) {
        continue;
      }

      $route->addRequirements(['_archived_route_access_check' => 'TRUE']);
    }

    $this->alterGroupInviteRoutes($collection);
    $this->alterGroupPagesRoutes($collection);
  }

  /**
   * Alters group invite route definitions.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   */
  private function alterGroupInviteRoutes(RouteCollection $collection): void {
    $group_invite_route_names = [
      'ginvite.invitation.bulk',
      'ginvite.invitation.bulk.confirm',
    ];
    foreach ($group_invite_route_names as $route_name) {
      // Change route access permissions to "invite users to group".
      if (
        ($group_invite_route = $collection->get($route_name)) &&
        $group_invite_route instanceof Route
      ) {
        $group_invite_route->setRequirement('_group_permission', 'invite users to group');
        $group_invite_route->setRequirement('_group_invitation_bulk', 'TRUE');
      }

      // Overrides bulk group members invitation confirm form.
      if ($route_name === 'ginvite.invitation.bulk.confirm') {
        $group_invite_route->setDefault('_form', '\Drupal\eic_groups\Form\BulkGroupInvitationConfirm');
      }
    }
  }

  /**
   * Alters group pages route definitions.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   */
  private function alterGroupPagesRoutes(RouteCollection $collection): void {
    $target_routes = [
      'view.eic_group_members.page_group_members',
      'view.admin_blocked_entities.page_admin_group_blocked_history',
      'entity.group.edit_form',
    ];

    foreach ($target_routes as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->addRequirements([
          '_group_pages_access_check' => 'TRUE',
        ]);
      }
    }
  }

}
