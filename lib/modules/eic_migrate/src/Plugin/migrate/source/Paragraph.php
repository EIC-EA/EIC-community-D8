<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Paragraphs source plugin.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_paragraph_item
 *   field_name:
 *     - my_field_name
 *     - another_field
 *   bundle: (optional) the paragraph bundle to filter on
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_paragraph_item",
 *   source_module = "paragraphs"
 * )
 */
class Paragraph extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field_names = is_array($this->configuration['field_name']) ? $this->configuration['field_name'] : [$this->configuration['field_name']];

    $query = $this->select('paragraphs_item', 'pi')
      ->fields('pi', [
        'item_id',
        'field_name',
        'revision_id',
      ]);
    $query->condition('pi.field_name', $field_names, 'IN');

    if (!empty($this->configuration['bundle'])) {
      $query->condition('pi.bundle', $this->configuration['bundle']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $pid = $row->getSourceProperty('item_id');
    $vid = $row->getSourceProperty('revision_id');
    // Get Field API field values.
    foreach ($this->getFields('paragraphs_item', $this->configuration['bundle']) as $field_name => $field) {
      $row->setSourceProperty($field_name, $this->getFieldValues('paragraphs_item', $field_name, $pid, $vid, NULL));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('Item ID'),
      'revision_id' => $this->t('Revision ID'),
      'field_name' => $this->t('Name of field'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['item_id']['type'] = 'integer';
    $ids['item_id']['alias'] = 'pi';
    $ids['revision_id']['type'] = 'integer';
    $ids['revision_id']['alias'] = 'pi';
    return $ids;
  }

}
