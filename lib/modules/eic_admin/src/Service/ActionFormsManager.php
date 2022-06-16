<?php

namespace Drupal\eic_admin\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Path\PathValidatorInterface;
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
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

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
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
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
    PathValidatorInterface $path_validator,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    TitleResolverInterface $title_resolver
  ) {
    $this->routeMatch = $route_match;
    $this->pathMatcher = $path_matcher;
    $this->pathValidator = $path_validator;
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
   * @param string|null $path
   *   The path for which we want to retrieve the config. Defaults to current
   *   path.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|false
   *   The config object or FALSE if not found.
   */
  public function getRouteConfig(string $route_name = NULL, string $path = NULL) {
    if (empty($route_name)) {
      $route_name = $this->routeMatch->getRouteName();
    }
    if (empty($path)) {
      $path = $this->requestStack->getCurrentRequest()->getRequestUri();
    }

    foreach ($this->getAllRouteConfigs() as $config) {
      // We return the first item we find that both matches route and path.
      if ($config->get('route') == $route_name && $this->matchPath($config, $path)) {
        return $config;
      }
    }

    return FALSE;
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
   * @param string $target_path
   *   The path to check. Defaults to the current URI.
   *
   * @return bool
   *   TRUE if the config is applicable for the given path.
   */
  public function matchPath(ConfigBase $config, string $target_path = NULL): bool {
    $paths = [];
    if (strlen($config->get('paths')) > 0) {
      $paths = explode(PHP_EOL, $config->get('paths'));
    }

    if (empty($paths)) {
      return TRUE;
    }

    if (empty($target_path)) {
      $target_path = $this->requestStack->getCurrentRequest()->getRequestUri();
    }

    $match_path = FALSE;
    foreach ($paths as $path) {
      // We need to remove unrelated query params in order to match the path.
      // E.g. remove Drupal's 'destination' query param.
      $parts = UrlHelper::parse($path);
      $clean_target_path = $target_path;
      $query_params = $parts['query'];
      $clean_target_path = $this->stripOutUnwantedQueryParams($clean_target_path, array_keys($query_params));

      if ($this->pathMatcher->matchPath($clean_target_path, $path)) {
        $match_path = TRUE;
        break;
      }
    }

    return $match_path;
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

  /**
   * Removes unwanted query params from a URL string.
   *
   * @param string $path
   *   The URL.
   * @param array $allowed_params
   *   A list of params that are allowed.
   *
   * @return string
   *   The cleaned URL.
   */
  protected function stripOutUnwantedQueryParams(string $path, array $allowed_params) {
    $parts = UrlHelper::parse($path);
    // If the are no params, we return the URL.
    if (!$parts['query']) {
      return $path;
    }

    $query_params = $parts['query'];
    foreach ($query_params as $query_param => $value) {
      if (!in_array($query_param, $allowed_params)) {
        unset($query_params[$query_param]);
      }
    }

    // Rebuild the query string.
    $parts['query'] = UrlHelper::buildQuery($query_params);

    return http_build_url($parts);
  }

}
