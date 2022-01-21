<?php

namespace Drupal\eic_webservices\Utility;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Provides helper functions for EIC Webservices module.
 */
class EicWsHelper {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManager $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns a user object based on SMED ID field.
   *
   * @param int $smed_id
   *   The SME Dashboard ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The user entity or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserBySmedId(int $smed_id) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Find if a user account matches the given SMED ID.
    $entity_query = $this->entityTypeManager->getStorage('user')->getQuery();
    $entity_query->condition($smed_id_field, $smed_id);
    $entity_query->range(NULL, 1);
    $uids = $entity_query->execute();
    return empty($uids) ? NULL : $this->entityTypeManager->getStorage('user')->load(array_pop($uids));
  }

  /**
   * Returns a group object based on SMED ID field.
   *
   * In SMED each type has its own 'serial' id. We can hence have the same ID
   * for different bundles. This is why we need to filter per bundle.
   *
   * @param int $smed_id
   *   The SME Dashboard ID.
   * @param string $group_type
   *   The group type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The group entity or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupBySmedId(int $smed_id, string $group_type) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Find if a user account matches the given SMED ID.
    $entity_query = $this->entityTypeManager->getStorage('group')->getQuery();
    $entity_query->condition('type', $group_type);
    $entity_query->condition($smed_id_field, $smed_id);
    $entity_query->range(NULL, 1);
    $ids = $entity_query->execute();
    return empty($ids) ? NULL : $this->entityTypeManager->getStorage('group')->load(array_pop($ids));
  }

}
