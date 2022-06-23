<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 all node photo.
 *
 * @MigrateSource(
 *   id = "eic_d7_node_complete_photo",
 *   source_module = "node"
 * )
 */
class NodeCompletePhoto extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $query->leftJoin('field_data_c4m_media', 'm', 'n.nid = m.entity_id AND m.bundle = :photo_bundle', [':photo_bundle' => 'photo']);
    // Include image alt and title text.
    $query->leftJoin('field_data_field_file_image_alt_text', 'a', 'm.c4m_media_fid = a.entity_id');
    $query->leftJoin('field_data_field_file_image_title_text', 't', 'm.c4m_media_fid = t.entity_id');
    $query->fields('a', ['field_file_image_alt_text_value']);
    $query->fields('t', ['field_file_image_title_text_value']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $alt_text = $row->getSourceProperty('field_file_image_alt_text_value');
    if (empty($alt_text)) {
      $row->setSourceProperty('field_file_image_alt_text_value', NULL);
    }
    $title_text = $row->getSourceProperty('field_file_image_title_text_value');
    if (empty($title_text)) {
      $row->setSourceProperty('field_file_image_title_text_value', NULL);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['field_file_image_alt_text_value'] = $this->t('Photo alt text.');
    $fields['field_file_image_alt_text_value'] = $this->t('Photo title text.');
    return $fields;
  }

}
