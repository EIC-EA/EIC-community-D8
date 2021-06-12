<?php

namespace Drupal\eic_flags\Routing;

use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\DeleteRequestHandler;
use Drupal\eic_flags\Service\HandlerInterface;
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
    $collector = \Drupal::service('eic_flags.handler_collector');
    $delete_request_handler = $collector->getHandlerByType(RequestTypes::DELETE);
    $available_types = array_map(function (HandlerInterface $handler) {
      return $handler->getType();
    }, $collector->getHandlers());
    $flag_type_list = implode('|', $available_types);


    $route_collection = new RouteCollection();
    // TODO change this in another PR to loop through every request handler
    // We must define a route for each supported entity type and set the 'entity_form' default on the route
    // This will ensure that the form controller handling this request is "HtmlEntityFormController"
    foreach (array_keys($delete_request_handler->getSupportedEntityTypes()) as $entity_type) {
      $route = (new Route('/' . $entity_type . '/{' . $entity_type . '}/request-delete'))
        ->addDefaults([
          '_entity_form' => $entity_type . '.request-delete',
          '_title' => 'Delete',
        ])
        ->setRequirement($entity_type, '\d+')
        ->setRequirement('_role', 'trusted_user+administrator+content_administrator');

      $route_collection->add('entity.' . $entity_type . '.request_delete_form', $route);

      // Define the route which apply the given operation and close the request
      $route = (new Route('/admin/community/' . $entity_type . '/{' . $entity_type . '}/request/{request_type}/close'))
        ->addDefaults([
          '_entity_form' => $entity_type . '.close-request',
          '_title' => t('Close request'),
        ])
        ->setRequirement($entity_type, '\d+')
        ->setRequirement('_role', 'administrator+content_administrator')
        ->setRequirement('request_type', $flag_type_list)
        ->setOption('_admin_route', TRUE);

      $route_collection->add('entity.' . $entity_type . '.close_request', $route);
    }

    // Define the route displaying entities flagged for removal
    $route = (new Route('/admin/community/request/{request_type}'))
      ->addDefaults([
        '_controller' => 'Drupal\eic_flags\Controller\FlagRequestController::listing',
      ])
      ->setRequirement('_role', 'administrator+content_administrator')
      ->setRequirement('request_type', $flag_type_list)
      ->setOption('_admin_route', TRUE);

    $route_collection->add('eic_flags.flagged_entities.list', $route);

    return $route_collection;
  }

}
