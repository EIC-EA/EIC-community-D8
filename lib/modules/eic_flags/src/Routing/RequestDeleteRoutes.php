<?php

namespace Drupal\eic_flags\Routing;

use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\DeleteRequestManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RequestDeleteRoutes
 *
 * @package Drupal\eic_flags\Routing
 */
class RequestDeleteRoutes {

  /**
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function routes() {
    $route_collection = new RouteCollection();

    // We must define a route for each supported entity type and set the 'entity_form' default on the route
    // This will ensure that the form controller handling this request is "HtmlEntityFormController"
    foreach (array_keys(DeleteRequestManager::$supportedEntityTypes) as $entity_type) {
      $route = (new Route('/' . $entity_type . '/{' . $entity_type . '}/request-delete'))
        ->addDefaults([
          '_entity_form' => $entity_type . '.request-delete',
          '_title' => 'Delete',
        ])
        ->setRequirement($entity_type, '\d+')
        ->setRequirement('_role', 'trusted_user+administrator+content_administrator');

      $route_collection->add('entity.' . $entity_type . '.request_delete_form', $route);
    }


    $flag_type_list = implode('|', [RequestTypes::DELETE]);
    // Define the route which apply the given operation and close the request
    $route = (new Route('/admin/community/{entity_type}/{entity_id}/request/flag_type}/close'))
      ->addDefaults([
        '_controller' => 'Drupal\eic_flags\Controller\FlagRequestController::close',
      ])
      ->setRequirement('_role', 'administrator+content_administrator')
      ->setRequirement('flag_type', $flag_type_list)
      ->setOption('_admin_route', TRUE);

    $route_collection->add('eic_flags.request.close', $route);

    // Define the route displaying entities flagged for removal
    $route = (new Route('/admin/community/request/{flag_type}'))
      ->addDefaults([
        '_controller' => 'Drupal\eic_flags\Controller\FlagRequestController::listing',
      ])
      ->setRequirement('_role', 'administrator+content_administrator')
      ->setRequirement('flag_type', $flag_type_list)
      ->setOption('_admin_route', TRUE);

    $route_collection->add('eic_flags.flagged_entities.list', $route);

    return $route_collection;
  }

}
