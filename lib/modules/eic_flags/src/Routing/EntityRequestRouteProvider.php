<?php

namespace Drupal\eic_flags\Routing;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class EntityRequestRouteProvider.
 *
 * @package Drupal\eic_flags\Routing
 */
class EntityRequestRouteProvider implements EntityRouteProviderInterface,
                                            EntityHandlerInterface {

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
        'new-request'
      );
      if ($new_request_route) {
        $new_request_route->setRequirement('_request_send_access', 'TRUE');

        $collection->add(
          'entity.' . $entity_type->id() . '.new_request',
          $new_request_route
        );
      }

      // Define a new request and close request route for the entity type.
      $close_request_route = $this->getRouteByTemplate(
        $entity_type,
        $handler,
        'close-request'
      );
      if ($close_request_route) {
        $close_request_route
          ->setRequirement('_permission', 'manage archival deletion requests')
          ->setOption('_admin_route', TRUE);

        $collection->add(
          'entity.' . $entity_type->id() . '.close_request',
          $close_request_route
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
   *
   * @return \Symfony\Component\Routing\Route|null
   *   Matching route or null.
   */
  protected function getRouteByTemplate(
    EntityTypeInterface $entity_type,
    HandlerInterface $handler,
    string $template
  ) {
    if (!$entity_type->hasLinkTemplate($template)) {
      return NULL;
    }

    $route = (new Route($entity_type->getLinkTemplate($template)))
      ->addDefaults([
        '_entity_form' => $entity_type->id() . '.' . $template,
        '_title' => ucfirst($handler->getType()),
      ])
      ->setRequirement($entity_type->id(), '\d+')
      ->setOption('entity_type_id', $entity_type->id());

    return $route;
  }

}
