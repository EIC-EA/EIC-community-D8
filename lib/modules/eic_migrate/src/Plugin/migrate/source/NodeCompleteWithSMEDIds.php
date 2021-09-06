<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\NodeComplete;

/**
 * Drupal 7 all node revisions source, including translation revisions.
 *
 * Also includes SMED IDs for taxonomy term references.
 *
 * Configuration keys:
 *  smed_taxonomy_fields:
 *    - taxonomy_field_x
 *    - taxonomy_field_y
 *
 *
 * For all available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "eic_d7_node_complete_with_smed_ids",
 *   source_module = "node"
 * )
 */
class NodeCompleteWithSMEDIds extends NodeComplete {

  /**
   * The SMED taxonomy reference fields.
   *
   * @var array
   */
  protected $smedTaxonomyFields;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager, $module_handler);

    $this->smedTaxonomyFields = !empty($configuration['smed_taxonomy_fields']) ? $configuration['smed_taxonomy_fields'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);

    foreach ($this->smedTaxonomyFields as $field) {
      $fieldValues = $row->getSourceProperty($field);
      if (!empty($fieldValues)) {
        $row->setSourceProperty($field, $this->getTaxonomyFieldValuesWithSmedIds($fieldValues));
      }
    }

    return $result;
  }

  /**
   * Returns the given field values with their SMED ID, if found.
   *
   * @param array $fieldValues
   *   The array of source field values. Should include taxonomy tid.
   *
   * @return array
   *   The array of new source field values. Includes SMED ids if present.
   */
  protected function getTaxonomyFieldValuesWithSmedIds(array $fieldValues): array {
    foreach ($fieldValues as $key => $fieldValue) {
      $dashboardKeyValues = $this->getFieldValues('taxonomy_term', 'c4m_dashboard_key', $fieldValue['tid']);
      if (isset($dashboardKeyValues[0]['value'])) {
        $fieldValue['smed_id'] = $dashboardKeyValues[0]['value'];
      }
      $fieldValues[$key] = $fieldValue;
    }

    return $fieldValues;
  }

}
