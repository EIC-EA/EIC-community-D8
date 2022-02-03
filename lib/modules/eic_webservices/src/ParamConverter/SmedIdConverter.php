<?php

namespace Drupal\eic_webservices\ParamConverter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity SMED IDs to full objects.
 *
 * @see \Drupal\Core\ParamConverter\EntityConverter
 */
class SmedIdConverter extends EntityConverter {

  /**
   * The prefix of eligible routes.
   *
   * @var string
   */
  protected const ROUTE_PREFIX = '/smed/api/';

  /**
   * All the routes that use a group SMED ID.
   *
   * @var string[]
   */
  protected const GROUP_ROUTES = [
    '/smed/api/v1/event/{group}',
    '/smed/api/v1/event/update/{group}',
    '/smed/api/v1/organisation/{group}',
    '/smed/api/v1/organisation/update/{group}',
  ];

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $bundle = NULL;
    if ($definition['type'] == 'entity:group') {
      /** @var \Symfony\Component\Routing\Route $route */
      $route = $defaults['_route_object'];
      $bundle = $this->getGroupType($route);
    }

    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
    $query->condition($smed_id_field, $value);
    if ($bundle) {
      $query->condition('type', $bundle);
    }
    if (!$entity_ids = $query->execute()) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage($entity_type_id)->load(reset($entity_ids));
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // Check the endpoint path prefix first.
    if (strpos($route->getPath(), self::ROUTE_PREFIX) !== 0) {
      return FALSE;
    }

    // Define an array of applicable entity types and parameter names.
    $types = [
      'entity:group' => 'group',
      'entity:user' => 'user',
    ];

    foreach ($types as $entity_type => $parameter_name) {
      if (isset($route->getOption('parameters')[$parameter_name]) &&
        !empty($definition['type']) && $definition['type'] == $entity_type) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Finds out which group type is being handled based on the provided route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   *
   * @return string|false
   *   The group type for the endpoint or FALSE if not found.
   */
  protected function getGroupType(Route $route) {
    if (!in_array($route->getPath(), self::GROUP_ROUTES)) {
      return FALSE;
    }

    $regex = '/^\/smed\/api\/(?<api_version>v[0-9]*)\/(?<group_type>\w+)/';
    preg_match($regex, $route->getPath(), $matches);

    return $matches['group_type'] ?? FALSE;
  }

  /**
   * Injects the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function setConfigFactory(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

}
