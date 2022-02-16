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

    $this->alterGroupInviteRoutes($collection);
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

}
