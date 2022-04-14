<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

/**
 * Drupal 7 group path redirect source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_group_path_redirect",
 *   source_module = "redirect"
 * )
 */
class GroupPathRedirect extends PathRedirect {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('purl', 'p')->orderBy('p.id');
    $query->addField('p', 'id', 'group_id');
    $query->addField('p', 'value', 'source');

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
    ];
  }

}
