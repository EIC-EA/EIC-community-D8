<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_url_alias_to_path_redirect",
 *   source_module = "redirect"
 * )
 */
class UrlAliasToPathRedirect extends PathRedirect {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('url_alias', 'ua')->orderBy('pid');

    // Only url alias from the configured entity type.
    if (!empty($this->entityType)) {
      $query->condition('ua.source', $this->entityType . '/%', 'LIKE');
    }

    $query->addField('ua', 'pid', 'rid');
    $query->addField('ua', 'source', 'redirect');
    $query->addField('ua', 'alias', 'source');
    $query->addField('ua', 'language');

    return $query;
  }

}
