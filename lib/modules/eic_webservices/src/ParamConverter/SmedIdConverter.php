<?php

namespace Drupal\eic_webservices\ParamConverter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity SMED IDs to full objects.
 *
 * @see \Drupal\Core\ParamConverter\EntityConverter
 */
class SmedIdConverter extends EntityConverter {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
    $entity_type_manager = isset($this->entityTypeManager)
      ? $this->entityTypeManager
      : $this->entityManager;
    if ($storage = $entity_type_manager->getStorage($entity_type_id)) {
      if (!$entities = $storage->loadByProperties([$smed_id_field => $value])) {
        return NULL;
      }
      $entity = reset($entities);
      return $entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    $types = [
      'entity:user',
    ];
    if (strpos($route->getPath(), '/smed/api/') === 0 &&
      isset($route->getOption('parameters')['user']) &&
      !empty($definition['type']) && in_array($definition['type'], $types)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Injects the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager to get the current content language.
   */
  public function setLanguageManager(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
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
