<?php

namespace Drupal\eic_dashboards\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Routing\RouteProviderInterface;

/**
 * Implements dashboards helpers.
 */
class DashboardHelper implements DashboardHelperInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RouteProviderInterface $routeProvider,
    UrlGeneratorInterface $urlGenerator
  ) {
    $this->routeProvider = $routeProvider;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('url_generator'),
    );
  }

  /**
   * Get title from routing.
   */
  public function getRoutingTitle($route_name): string {
    $route = $this->routeProvider->getRouteByName($route_name);
    return (string) $route->getDefault('_title');
  }

  /**
   * Get URL from routing.
   */
  public function getRoutingUrl($route_name): string {
    return $this->urlGenerator->generateFromRoute($route_name, [], ['absolute' => TRUE]);
  }

}
