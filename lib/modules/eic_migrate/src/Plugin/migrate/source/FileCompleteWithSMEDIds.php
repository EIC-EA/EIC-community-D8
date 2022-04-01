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
  public function query() {
    $query = parent::query();

    if ($this->fileType === 'document') {
      $query->leftJoin('field_data_c4m_document', 'd', 'f.fid = d.c4m_document_fid AND d.bundle = :bundle', [':bundle' => 'document']);
      $query->fields('d', ['entity_id']);
      $query->leftJoin('field_data_c4m_vocab_language', 'l', 'l.entity_id = d.entity_id');
      $query->addField('l', 'c4m_vocab_language_tid', 'c4m_vocab_language');
    }

    return $query;
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

  /**
   * Retrieves field values for a single field of a single entity.
   *
   * Typically, getFieldValues() is used in the prepareRow method of a source
   * plugin where the return values are placed on the row source.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   * @param int $entity_id
   *   The entity ID.
   * @param int|null $revision_id
   *   (optional) The entity revision ID.
   * @param string $language
   *   (optional) The field language.
   *
   * @return array
   *   The raw field values, keyed and sorted by delta.
   */
  protected function getFieldValues($entity_type, $field, $entity_id, $revision_id = NULL, $language = NULL) {
    $table = (isset($revision_id) ? 'field_revision_' : 'field_data_') . $field;
    $query = $this->select($table, 't')
      ->fields('t')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('deleted', 0)
      ->orderBy('delta');
    if (isset($revision_id)) {
      $query->condition('revision_id', $revision_id);
    }
    // Add 'language' as a query condition if it has been defined by Entity
    // Translation.
    if ($language) {
      $query->condition('language', $language);
    }
    $values = [];
    foreach ($query->execute() as $row) {
      foreach ($row as $key => $value) {
        $delta = $row['delta'];
        if (strpos($key, $field) === 0) {
          $column = substr($key, strlen($field) + 1);
          $values[$delta][$column] = $value;
        }
      }
    }
    return $values;
  }

}
