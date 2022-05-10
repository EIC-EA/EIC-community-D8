<?php

namespace Drupal\eic_admin\Service;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;

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
  public $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ShareManager object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory) {
    $this->routeMatch = $route_match;
    $this->configFactory = $config_factory;
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

}
