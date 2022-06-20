<?php

namespace Drupal\eic_admin\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\eic_admin\Service\ActionFormsManager;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The action forms manager service.
   *
   * @var \Drupal\eic_admin\Service\ActionFormsManager
   */
  protected $actionFormsManager;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\eic_admin\Service\ActionFormsManager $action_forms_manager
   *   The action forms manager service.
   */
  public function __construct(ActionFormsManager $action_forms_manager) {
    $this->actionFormsManager = $action_forms_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Make sure our implementation runs after other modules.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Alter action forms routes.
    $routes = $this->actionFormsManager->getAllRouteConfigs();
    foreach ($routes as $config) {
      // If title is provided, we override the title callback.
      if ($route = $collection->get($config->get('route'))) {
        $title = $config->get('title');

        if (!empty($title)) {
          $route->setDefault('_title_callback', '\Drupal\eic_admin\Controller\ActionFormsController::pageTitle');
        }
        // Store the parameters in a custom variable for later use.
        $entity_types = [];
        if ($route->hasOption('parameters')) {
          foreach ($route->getOption('parameters') as $param) {
            if (!empty($param['type'])) {
              // We split the 'entity:entity_type' value tot get the entity type
              // only.
              $entity_types[] = explode(':', $param['type'])[1];
            }
          }
        }
        $route->setDefault('_entity_types', $entity_types);
      }
    }
  }

}
