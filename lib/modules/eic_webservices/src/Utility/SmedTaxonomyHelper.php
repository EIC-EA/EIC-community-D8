<?php

namespace Drupal\eic_webservices\Utility;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Provides helper functions for the SMED taxonomy webservice.
 */
class SmedTaxonomyHelper {

  /**
   * The list of taxonomies imported from the SMED.
   *
   * @todo Replace with a dynamic way?
   *
   * @var string[]
   */
  protected const SMED_VOCABULARIES = [
    'global_event_type',
    'target_markets',
    'job_titles',
    'languages',
    'topics',
  ];

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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   An instance of ConfigFactory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    ConfigFactory $config_factory,
    EntityTypeManager $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Returns the term ID for the given SMED ID.
   *
   * @param string $smed_id
   *   The SMED ID to look for.
   * @param string $vocabulary_name
   *   The taxonomy vocabulary to search for.
   *
   * @return int|null
   *   The Term ID or NULL if not found.
   */
  public function getTaxonomyTermIdBySmedId(string $smed_id, string $vocabulary_name) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery();
    $query->condition($smed_id_field, $smed_id);
    $query->condition('vid', $vocabulary_name);
    $ids = $query->execute();
    return empty($ids) ? NULL : reset($ids);
  }

  /**
   * Defines if a vocabulary is imported from SMED.
   *
   * @param string $vocabulary_name
   *   The vocabulary machine name.
   *
   * @return bool
   *   TRUE if vocabulary is being imported from SMED.
   */
  public function isSmedVocabulary(string $vocabulary_name) {
    return in_array($vocabulary_name, static::SMED_VOCABULARIES);
  }

  /**
   * Converts all SMED taxonomy fields value to Drupal Term id.
   *
   * Assuming that the given entity contains taxonomy field values with SMED
   * IDs, this function will convert those values to Drupal term IDs.
   * This function does not dive into nested entities (e.g. paragraphs). You
   * need to provide them separately.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to convert.
   * @param string[] $field_names
   *   Array of field names to convert. Leave empty to convert them all.
   */
  public function convertEntitySmedTaxonomyIds(EntityInterface &$entity, array $field_names = []) {
    // Get entity field definitions.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    // Cycle through all entity reference fields.
    foreach ($field_definitions as $field_definition) {
      // We treat entity_reference fields only.
      if ($field_definition->getType() != 'entity_reference') {
        continue;
      }

      // Check for taxonomy term reference fields only.
      if ($field_definition->getSetting('target_type') != 'taxonomy_term') {
        continue;
      }

      $field_name = $field_definition->getName();

      // Check if we need to handle this field.
      if (!empty($field_names) && !in_array($field_name, $field_names)) {
        continue;
      }

      // Check if this is a SMED vocabulary.
      $handler_settings = $field_definition->getSetting('handler_settings');
      $vid = reset($handler_settings['target_bundles']);
      if (!$this->isSmedVocabulary($vid)) {
        continue;
      }

      // If field is empty, skip it.
      if ($entity->{$field_name}->isEmpty()) {
        continue;
      }

      // Cycle through all values to get the term IDs.
      $target_ids = [];
      foreach ($entity->{$field_name} as $value) {
        $smed_id = $value->target_id;
        $target_ids[] = $this->getTaxonomyTermIdBySmedId($smed_id, $vid);
      }

      // Replace old values with the new ones.
      $entity->{$field_name} = $target_ids;
    }
  }

  /**
   * Returns the parent term ID for the given vocabulary and child term.
   *
   * This function tries to guess the parent term ID, based on the given child
   * term ID. It does so with some logic based on the webservice output.
   *
   * @param string $vocabulary_name
   *   The vocabulary identifier provided by SMED webservice.
   * @param string $child_id
   *   The ID of the child term for which we want to find the parent.
   *
   * @return string|null
   *   The matched parent ID or 0 if parent ID couldn't be resolved.
   */
  public static function findTermParentId(string $vocabulary_name, string $child_id) {
    switch ($vocabulary_name) {
      case 'ThematicsTopics':
        return self::resolveThematicsTopicsParentId($child_id);
    }

    return NULL;
  }

  /**
   * Returns the parent ID for the given Child term ID.
   *
   * This function is specific for the ThematicsTopics SMED vocabulary.
   * The ID structure is as follows:
   * - T
   *   - T1
   *   - T2
   *   - ...
   * - H
   *   - H1
   *   - H2
   *     - H2-1
   *     - H2-2
   *     - ...
   *
   * Where T stands for "Thematic" and H "Horizontal".
   *
   * @param string $child_id
   *   The ID of the child term for which we want to find the parent.
   *
   * @return string|null
   *   The matched parent ID or NULL if parent ID couldn't be resolved.
   */
  protected static function resolveThematicsTopicsParentId(string $child_id) {
    $regex = '/^((H|T)(?:[0-9]*))(?:-[0-9]*)?$/';
    preg_match($regex, $child_id, $matches);
    // This regex always returns 3 matches, which represent the 3 levels. We
    // return the first one that is different from the original one.
    foreach ($matches as $item) {
      if ($item != $child_id) {
        return $item;
      }
    }
    return NULL;
  }

}
