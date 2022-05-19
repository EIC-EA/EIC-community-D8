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
 *   plugin: eic_d7_c4m_og_invite_visitors
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_d7_c4m_og_invite_visitors",
 *   source_module = "eic_migrate",
 * )
 */
class C4mOgInviteVisitors extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('c4m_og_invite_visitors', 'coiv');
    $query->fields('coiv');
    $query->innerJoin('node', 'n', 'n.nid = coiv.inv_group_id');
    $query->addField('n', 'type', 'node_type');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'inv_id' => $this->t('ID'),
      'inv_uid' => $this->t('User ID'),
      'inv_inviter_id' => $this->t('Inviter ID'),
      'inv_group_id' => $this->t('Group ID'),
      'inv_email' => $this->t('Invitee email'),
      'inv_created' => $this->t('Created date'),
      'inv_updated' => $this->t('Updated date'),
      'inv_token' => $this->t('Token'),
      'inv_expired' => $this->t('Expired'),
      'node_type' => $this->t('Node (group) bundle'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'inv_id' => [
        'type' => 'integer',
        'alias' => 'coiv',
      ],
    ];
    return $ids;
  }

}
