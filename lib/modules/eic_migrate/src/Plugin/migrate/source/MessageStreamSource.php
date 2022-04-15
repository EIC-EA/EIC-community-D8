<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\message\Plugin\migrate\source\MessageSource;
use Drupal\migrate\Row;

/**
 * Drupal 7 message stream source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_message_stream_source",
 *   source_module = "message"
 * )
 */
class MessageStreamSource extends MessageSource {

  const MESSAGE_TYPES = [
    'c4m_insert__comment',
    'c4m_insert__node__article',
    'c4m_insert__node__discussion',
    'c4m_insert__node__document',
    'c4m_insert__node__event',
    'c4m_insert__node__news',
    'c4m_insert__node__photoalbum',
    'c4m_insert__node__share',
    'c4m_insert__node__wiki_page',
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Limit to stream messages.
    $query->condition('m.type', self::MESSAGE_TYPES, 'IN');
    // Joins field_data_field_node table to get the related node ID.
    $query->leftJoin('field_data_field_node', 'fn', "fn.entity_id = m.mid AND fn.entity_type = 'message'");
    $query->addField('fn', 'field_node_target_id', 'field_node');
    // Joins field_data_field_group_node table to get the related group ID.
    $query->leftJoin('field_data_field_group_node', 'fgn', "fgn.entity_id = m.mid AND fgn.entity_type = 'message'");
    $query->addField('fgn', 'field_group_node_target_id', 'field_group_node');
    // Joins field_data_field_comment table to get the related comment ID.
    $query->leftJoin('field_data_field_comment', 'fc', "fc.entity_id = m.mid AND fc.entity_type = 'message'");
    $query->addField('fc', 'field_comment_target_id', 'field_comment');
    // Joins field_data_field_operation table to get the stream operation.
    $query->leftJoin('field_data_field_operation', 'fo', "fo.entity_id = m.mid AND fo.entity_type = 'message'");
    $query->addField('fo', 'field_operation_value', 'field_operation');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);

    if ($row->getSourceProperty('type') === 'c4m_insert__node__share') {
      $row->setSourceProperty('field_operation', 'share');
    }

    return $result;
  }

}
