<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Drupal 7 file complete source from database including SMED taxonomies.
 *
 * This includes the field_file_image_alt_text and field_file_image_title_text
 * fields data on top of the default d7_file data.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: d7_file
 *   file_type: image
 *   exclude_photos: true
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_file_complete_with_smed_ids",
 *   source_module = "file"
 * )
 */
class FileCompleteWithSMEDIds extends FileComplete {

  /**
   * The SMED taxonomy reference fields.
   *
   * @var array
   */
  protected $smedTaxonomyFields;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

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
        $row->setSourceProperty($field, $this->getTaxonomyFieldValuesWithSmedIds([['tid' => $fieldValues]]));
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
