<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

/**
 * Drupal 7 group old path redirect source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_group_old_path_redirect",
 *   source_module = "redirect"
 * )
 */
class GroupOldPathRedirect extends PathRedirect {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('purl', 'p')->orderBy('p.id');
    $query->innerJoin('c4m_og_purl_aliases', 'pa', 'pa.new_path = p.value');
    $query->addField('p', 'id', 'group_id');
    $query->addField('pa', 'old_path', 'source');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'group_id' => [
        'type' => 'integer',
      ],
      'source' => [
        'type' => 'string',
      ],
    ];
  }

}
