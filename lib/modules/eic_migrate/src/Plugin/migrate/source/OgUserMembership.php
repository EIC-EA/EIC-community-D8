<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Get OG User membership from D7.
 *
 * Usage:
 *
 * @code
 * source:
 *   plugin: eic_d7_og_user_membership
 *   type: (optional) og_membership_type_default
 *   state: (optional) 1
 *   include_roles: (optional) true if you want to join the og_users_roles
 *     table. Defaults to false.
 *   ignore_owner: (optional) true if you want to ignore the owner membership.
 *     Defaults to false.
 *   include_user_data: (optional) true if you want include user's data from
 *     users table. Defaults to false.
 *   invitations_only: (optional) true if you want to get invitations only.
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
 *   id = "eic_d7_og_user_membership",
 *   source_module = "eic_migrate",
 * )
 */
class OgUserMembership extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('og_membership', 'ogm');
    $query->innerJoin('node', 'n', 'n.nid = ogm.gid');
    $query->fields('ogm');
    $query->addField('n', 'type', 'node_type');
    $query->addField('n', 'uid', 'group_owner');
    $query->condition('ogm.entity_type', 'user');

    if (!empty($this->configuration['type'])) {
      $query->condition('ogm.type', $this->configuration['type']);
    }
    if (!empty($this->configuration['state'])) {
      $query->condition('ogm.state', $this->configuration['state']);
    }
    if (!empty($this->configuration['ignore_owner'])) {
      $query->where('n.uid != ogm.etid');
    }
    if (!empty($this->configuration['include_roles'])) {
      $query->leftJoin('og_users_roles', 'ogur', 'ogm.etid = ogur.uid AND ogm.gid = ogur.gid');
      $query->addField('ogur', 'rid');
    }
    if (!empty($this->configuration['include_user_data'])) {
      $query->leftJoin('users', 'u', 'ogm.etid = u.uid');
      $query->fields('u');
    }
    if (!empty($this->configuration['invitations_only'])) {
      $query->innerJoin('field_data_og_membership_invitation', 'omi', 'omi.entity_id = ogm.id');
      $query->condition('omi.og_membership_invitation_value', 1);
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
