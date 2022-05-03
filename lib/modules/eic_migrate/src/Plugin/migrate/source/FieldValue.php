<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 field value source from database.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_field_value
 *   field_name: some_field_name
 *   entity_type: (optional) some_entity_type
 *   bundle: (optional) some_bundle
 *   ids:
 *     some_db_column:
 *       type: integer
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_field_value",
 *   source_module = "eic_migrate",
 * )
 */
class FieldValue extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_revision_' . $this->configuration['field_name'], 'fr')
      ->fields('fr');
    if (!empty($this->configuration['entity_type'])) {
      $query->condition('entity_type', $this->configuration['entity_type']);
    }
    if (!empty($this->configuration['bundle'])) {
      $query->condition('bundle', $this->configuration['bundle']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'entity_type' => $this->t('entity_type'),
      'bundle' => $this->t('bundle'),
      'deleted' => $this->t('deleted'),
      'entity_id' => $this->t('entity_id'),
      'revision_id' => $this->t('revision_id'),
      'language' => $this->t('language'),
      'delta' => $this->t('delta'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'g',
      ],
      'revision_id' => [
        'type' => 'integer',
        'alias' => 'g',
      ],
    ];
    if ($this->configuration['ids'] && is_array($this->configuration['ids'])) {
      $ids = array_merge($ids, $this->configuration['ids']);
    }
    return $ids;
  }

}
