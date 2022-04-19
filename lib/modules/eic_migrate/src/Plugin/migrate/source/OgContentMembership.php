<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Get OG Content membership from D7.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_og_content_membership
 *   type: (optional) og_membership_type_default
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_og_content_membership",
 *   source_module = "eic_migrate",
 * )
 */
class OgContentMembership extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_membership', 'ogm');
    $query->innerJoin('node', 'n', 'n.nid = ogm.etid');
    $query->fields('ogm');
    $query->addField('n', 'type', 'node_type');
    $query->condition('ogm.entity_type', 'node');

    if ($this->configuration['type']) {
      $query->condition('ogm.type', $this->configuration['type']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('ID'),
      'type' => $this->t('Type'),
      'etid' => $this->t('Entity ID'),
      'entity_type' => $this->t('Entity type'),
      'gid' => $this->t('Group ID'),
      'group_type' => $this->t('Group type'),
      'state' => $this->t('State'),
      'created' => $this->t('Created date'),
      'field_name' => $this->t('Field name'),
      'language' => $this->t('Language'),
      'node_type' => $this->t('Node (group) bundle'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'id' => [
        'type' => 'integer',
        'alias' => 'ogm',
      ],
    ];
    return $ids;
  }

}
