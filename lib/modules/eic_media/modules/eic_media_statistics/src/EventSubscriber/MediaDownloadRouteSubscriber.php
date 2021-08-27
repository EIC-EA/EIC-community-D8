<?php

namespace Drupal\eic_media_statistics\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * EIC Media route subscriber.
 */
class MediaDownloadRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection->all() as $route) {
      // Set custom access check for media download route.
      if (strpos($route->getPath(), '/media/{media}/download') === 0) {
        $route->setRequirement('_media_file_download_access', 'TRUE');
        $route->setDefault('_controller', '\Drupal\eic_media_statistics\Controller\MediaFileDownloadController::download');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
