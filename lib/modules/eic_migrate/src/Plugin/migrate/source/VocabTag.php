<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Vocabulary Tag source plugin.
 *
 * @MigrateSource(
 *   id = "eic_d7_vocab_tag",
 *   source_module = "eic_migrate",
 * )
 */
class VocabTag extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Select node in its last revision.
    $query = $this->select('taxonomy_term_data', 't')
      ->fields(
        't',
        [
          'tid',
          'name',
          'description',
          'format',
        ]
      );

    // Only vocabularies whose machine name
    // starts with 'c4m_vocab_tag_'.
    $query->join('taxonomy_vocabulary', 'v', 'v.vid=t.vid');
    $query->fields('v', ['machine_name']);
    $query->condition('v.machine_name', '%c4m_vocab_tag_%', 'LIKE');

    // Check the parent id..
    $query->join('taxonomy_term_hierarchy', 'h', 'h.tid=t.tid');
    $query->fields('h', ['parent']);

    // Only for existing projects/groups.
    $query->join('node', 'n', 'n.nid=SUBSTRING(v.machine_name, 15)');
    $query->fields('n', ['nid']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('Taxonomy Term ID'),
      'vid' => $this->t('Vocabulary ID'),
      'name' => $this->t('Taxonomy Term name'),
      'description' => $this->t('The Taxonomy Term description'),
      'format' => $this->t('The Taxonomy Term description format'),
      'machine_name' => $this->t('The Taxonomy Term vocabulary machine name'),
      'parent' => $this->t('The Taxonomy Term Parent ID'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    $ids['tid']['alias'] = 'ic';
    return $ids;
  }

}
