<?php

namespace Drupal\eic_flags\Routing;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\Service\HandlerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RequestRoutes.
 *
 * @package Drupal\eic_flags\Routing
 */
class RequestRoutes {

  use StringTranslationTrait;

  /**
   * Add routes for archival and delete request related features.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection
   */
  public function routes() {
    $collector = \Drupal::service('eic_flags.handler_collector');
    $available_types = array_map(
      function (HandlerInterface $handler) {
        return $handler->getType();
      },
      $collector->getHandlers()
    );
    $flag_type_list = implode('|', $available_types);

    $route_collection = new RouteCollection();
    // Define the route displaying flagged entities.
    $route = (new Route('/admin/community/request/{request_type}'))
      ->addDefaults([
        '_controller' => 'Drupal\eic_flags\Controller\FlagRequestController::listing',
        '_title_callback' => 'Drupal\eic_flags\Controller\FlagRequestController::getTitle',
      ])
      ->setRequirement('_permission', 'manage archival deletion requests')
      ->setRequirement('request_type', $flag_type_list)
      ->setOption('_admin_route', TRUE);

    $route_collection->add('eic_flags.flagged_entities.list', $route);

    // Define the route displaying flags for a content entity.
    $route = (new Route(
      '/admin/community/{entity_type}/{entity_id}/{request_type}/detail'
    ))
      ->addDefaults([
        '_controller' => 'Drupal\eic_flags\Controller\FlagRequestController::detail',
      ])
      ->setRequirement('_permission', 'manage archival deletion requests')
      ->setRequirement('request_type', $flag_type_list)
      ->setOption('_admin_route', TRUE);

    $route_collection->add('eic_flags.flagged_entity.detail', $route);

    $route = (new Route('/admin/publish/{entity_type_id}/{entity_id}'))
      ->addDefaults([
        '_controller' => 'Drupal\eic_flags\Controller\FlagRequestController::publish',
      ])
      ->setRequirement('_permission', 'manage archival deletion requests')
      ->setOption('_admin_route', TRUE);

    $route_collection->add('eic_flags.publish_archived_content', $route);

    return $route_collection;
  }

}
