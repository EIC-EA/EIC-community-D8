<?php

namespace Drupal\eic_admin\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class that manages functions for the confirmation forms.
 *
 * @package Drupal\eic_admin\Service
 */
class ActionFormsManager {

  use StringTranslationTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The title resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Constructs a new ShareManager object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    TitleResolverInterface $title_resolver
  ) {
    $this->routeMatch = $route_match;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->titleResolver = $title_resolver;
  }

  /**
   * Get the config for the given route.
   *
   * @param string|null $route_name
   *   The route name for which we want to retrieve the config. Defaults to
   *   current route.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|false
   *   The config object or FALSE if not found.
   */
  public function getRouteConfig(string $route_name = NULL) {
    if (empty($route_name)) {
      $route_name = $this->routeMatch->getRouteName();
    }

    return $this->configFactory->get("eic_admin.action_forms.$route_name") ?? FALSE;
  }

  /**
   * Returns all the existing config objects for action forms.
   *
   * @return \Drupal\Core\Config\Config[]
   *   An array of editable config objects.
   */
  public function getAllRouteConfigs(): array {
    $configs = [];
    foreach ($this->configFactory->listAll('eic_admin.action_forms.') as $config_name) {
      if ($config = $this->configFactory->getEditable($config_name)) {
        $configs[$config_name] = $config;
      }
    }
    return $configs;
  }

  /**
   * Returns all the existing action form routes.
   *
   * @return string[]
   *   A list of route names.
   */
  public function getActionFormRoutes() {
    $routes = [];
    foreach ($this->getAllRouteConfigs() as $config) {
      $routes[] = $config->get('route');
    }
    return $routes;
  }

  /**
   * Returns the page title for the current request.
   *
   * @return string
   *   The page title.
   */
  public function getCurrentRequestPageTitle() {
    $title = '';
    $request = $this->requestStack->getCurrentRequest();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = $this->titleResolver->getTitle($request, $route);
    }
    return $title;
  }

}
