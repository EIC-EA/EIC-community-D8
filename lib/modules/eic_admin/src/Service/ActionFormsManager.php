<?php

namespace Drupal\eic_admin\Service;

use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Path\PathMatcherInterface;
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
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

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
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    PathMatcherInterface $path_matcher,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    TitleResolverInterface $title_resolver
  ) {
    $this->routeMatch = $route_match;
    $this->pathMatcher = $path_matcher;
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
   * Helper function to return the list of configured paths.
   *
   * @param \Drupal\Core\Config\ConfigBase $config
   *   The config object.
   *
   * @return string[]
   *   An array of paths.
   */
  public static function getConfigPaths(ConfigBase $config): array {
    $paths = [];
    if ($config->get('paths')) {
      $paths = explode(PHP_EOL, $config->get('paths'));
    }
    return $paths;
  }

  /**
   * Checks if the config should match the given path.
   *
   * If specific paths are defined for this config, we check if one of them
   * match the given path. Otherwise, if no path is defined, then we consider
   * all paths are allowed.
   *
   * @param \Drupal\Core\Config\ConfigBase $config
   *   The config object.
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if the config is applicable for the given path.
   */
  public function matchPath(ConfigBase $config, string $path): bool {
    $paths = $config->get('paths');

    if (empty($paths)) {
      return TRUE;
    }

    return $this->pathMatcher->matchPath($path, $paths);
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
