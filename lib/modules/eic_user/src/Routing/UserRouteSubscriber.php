<?php

namespace Drupal\eic_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class UserRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $edit_account_route = $collection->get('entity.user.edit_form');
    $edit_profile_route = $collection->get('entity.profile.edit_form');

    if ($edit_account_route) {
      $edit_account_route->setDefaults([
        '_entity_form' => 'user.default',
        '_title' => 'Edit my account',
      ]);
    }

    if ($edit_profile_route) {
      $edit_profile_route->setDefaults([
        '_entity_form' => 'profile.edit',
        '_title' => 'Edit my profile',
      ]);
    }
  }

}
