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
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$entities = $storage->loadByProperties([$smed_id_field => $value])) {
      return NULL;
    }

    $entity = reset($entities);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // Check the endpoint path prefix first.
    if (strpos($route->getPath(), '/smed/api/') !== 0) {
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
   * Injects the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function setConfigFactory(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

}
