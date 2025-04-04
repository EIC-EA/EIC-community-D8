<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Url;
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

  /**
   * Transforms data array into name and y format.
   */
  public function transformLabelCountToNameAndY($data): array {
    $result = [];

    foreach ($data as $item) {
      if (isset($item['label']) && isset($item['count'])) {
        $result[] = [
          'name' => $item['label'],
          'y' => $item['count'],
        ];
      }
    }

    return $result;
  }

  /**
   * Prepares data for jump menu, links to View with one argument.
   */
  public function prepareDataForJumpMenu($items, $viewMachineName, $routeParameters, $argumentIds): array {
    foreach ($items as $key => $item) {
      $query = [];
      foreach ($argumentIds as $argumentId) {
        if (isset($item[$argumentId])) {
          $query[$argumentId] = $item[$argumentId];
        }
      }

      $url = Url::fromRoute($viewMachineName, $routeParameters, ['query' => $query]);

      $links[$key] = [
        '#value' => $key,
        '#label' => $item['label'],
        '#attributes' => [
          'data-url' => $url->toString(),
        ],
      ];
    }

    if (!empty($links)) {
      return $links;
    }
    else {
      return [];
    }
  }

  /**
   * Encodes given data to JSON format for chart use.
   */
  public function jsonEncodeCategoriesSeries($data): array {
    return [
      'categories' => json_encode($data['categories']),
      'series' => json_encode($data['series'], JSON_NUMERIC_CHECK),
    ];
  }

  /**
   * Transforms data array into categories and series format.
   */
  public function transformIdCountToCategoriesSeries($data): array {
    $result = [
      'categories' => [],
      'series' => [],
    ];
    foreach ($data as $item) {
      if (isset($item['id']) && isset($item['count'])) {
        $result['categories'][] = $item['id'];
        $result['series'][] = $item['count'];
      }
    }

    return $result;
  }
}
