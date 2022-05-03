<?php

namespace Drupal\eic_flags\Routing;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Drupal\eic_flags\Controller\FlagRequestController;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides entity request routes.
 *
 * @package Drupal\eic_flags\Routing
 */
class EntityRequestRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {

  /**
   * The request collector service.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $collector;

  /**
   * RequestRouteProvider constructor.
   *
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The request collection service.
   */
  public function __construct(RequestHandlerCollector $collector) {
    $this->collector = $collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static($container->get('eic_flags.handler_collector'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    $request_handlers = $this->collector->getHandlers();
    foreach ($request_handlers as $handler) {
      $supported_types = array_keys($handler->getSupportedEntityTypes());
      // Do nothing if entity type is not supported by the handler.
      if (!in_array($entity_type->id(), $supported_types)) {
        continue;
      }

      // Define a new request and close request route for the entity type.
      $new_request_route = $this->getRouteByTemplate(
        $entity_type,
        $handler,
        'new-request',
        ['_entity_form' => $entity_type->id() . '.new-request']
      );
      if ($new_request_route) {
        $new_request_route->setRequirement('_request_send_access', 'TRUE');

        $collection->add(
          'entity.' . $entity_type->id() . '.new_request',
          $new_request_route
        );
      }

      $new_request_route_api = $this->getRouteByTemplate(
        $entity_type,
        $handler,
        'new-request-api',
        ['_controller' => '\Drupal\eic_flags\Controller\RequestEndpointController::request']
      );
      if ($new_request_route_api) {
        $new_request_route_api->setRequirement('_request_send_access', 'TRUE');

        $collection->add(
          'entity.' . $entity_type->id() . '.new_request_api',
          $new_request_route_api
        );
      }

      // Define a close request route for the entity type (route for
      // administrators).
      $admin_close_request_route = $this->getRouteByTemplate(
        $entity_type,
        $handler,
        'close-request',
        ['_entity_form' => $entity_type->id() . '.close-request']
      );
      if ($admin_close_request_route) {
        $admin_close_request_route->setRequirement('_close_request_access', 'TRUE')
          ->setOption('_admin_route', TRUE);
        $collection->add(
          'entity.' . $entity_type->id() . '.close_request',
          $admin_close_request_route
        );
      }

      // Define a close request route for the entity type (route for
      // non-administrators).
      $user_close_request_route = $this->getRouteByTemplate(
        $entity_type,
        $handler,
        'user-close-request',
        ['_entity_form' => $entity_type->id() . '.close-request']
      );
      if ($user_close_request_route) {
        $user_close_request_route->setRequirement('_close_request_access', 'TRUE');
        $collection->add(
          'entity.' . $entity_type->id() . '.user_close_request',
          $user_close_request_route
        );
      }

      // Define a cancel request route for the entity type (route for
      // non-administrators).
      $user_cancel_request_route = $this->getRouteByTemplate(
        $entity_type,
        $handler,
        'user-cancel-request',
        ['_entity_form' => $entity_type->id() . '.cancel-request']
      );
      if ($user_cancel_request_route) {
        $user_cancel_request_route->setRequirement('_cancel_request_access', 'TRUE');
        $collection->add(
          'entity.' . $entity_type->id() . '.user_cancel_request',
          $user_cancel_request_route
        );
      }
    }

    return $collection;
  }

  /**
   * Returns the route for the given template.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\eic_flags\Service\HandlerInterface $handler
   *   Handler of the current request type.
   * @param string $template
   *   The concerned template.
   * @param array $defaults
   *   (optional) An array of default parameter values.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   Matching route or null.
   */
  protected function getRouteByTemplate(
    EntityTypeInterface $entity_type,
    HandlerInterface $handler,
    string $template,
    array $defaults = []
  ) {
    if (!$entity_type->hasLinkTemplate($template)) {
      return NULL;
    }

    $route = (new Route($entity_type->getLinkTemplate($template)))
      ->addDefaults(
        [
          '_title_callback' => FlagRequestController::class . '::getRequestTitle',
        ] + $defaults)
      ->setRequirement($entity_type->id(), '\d+')
      ->setOption('entity_type_id', $entity_type->id());

    return $route;
  }

}
