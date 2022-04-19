<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Get OG membership from D7.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_og_membership
 *   entity_type: user
 *   type: (optional) og_membership_type_default
 *   state: (optional) 1
 *   include_roles: (optional) true if you want to join the og_users_roles
 *     table. Defaults to false.
 *   ignore_owner: (optional) true if you want to ignore the owner membership.
 *     Defaults to false.
 * @endcode
 *
 * 'state' option can be:
 * - 1: active.
 * - 2: pending. This probably relates to invited users and should hence not
 *      be imported as an actual membership.
 * - 3: blocked.
 *
 * @MigrateSource(
 *   id = "eic_d7_og_membership",
 *   source_module = "eic_migrate",
 * )
 */
class OgMembership extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_membership', 'ogm');
    $query->innerJoin('node', 'n', 'n.nid = ogm.gid');
    $query->fields('ogm');
    $query->addField('n', 'type', 'node_type');
    $query->addField('n', 'uid', 'group_owner');
    if ($this->configuration['entity_type']) {
      $query->condition('ogm.entity_type', $this->configuration['entity_type']);
    }
    if ($this->configuration['type']) {
      $query->condition('ogm.type', $this->configuration['type']);
    }
    if ($this->configuration['state']) {
      $query->condition('ogm.state', $this->configuration['state']);
    }
    if ($this->configuration['ignore_owner']) {
      $query->where('n.uid != ogm.etid');
    }
    if ($this->configuration['include_roles']) {
      $query->leftJoin('og_users_roles', 'ogur', 'ogm.etid = ogur.uid AND ogm.gid = ogur.gid');
      $query->addField('ogur', 'rid');
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
