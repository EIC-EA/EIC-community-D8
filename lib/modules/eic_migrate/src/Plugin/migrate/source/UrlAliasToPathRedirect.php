<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

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
   * TWether or not this migration should only include items with group prefix.
   *
   * @var bool
   */
  protected $includeGroupPrefix;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    StateInterface $state,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    $this->includeGroupPrefix = !empty($configuration['constants']['group_content_only']) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('url_alias', 'ua')->orderBy('pid');

    // Only url alias from the configured entity type.
    if (!empty($this->entityType)) {
      $query->condition('ua.source', $this->entityType . '/%', 'LIKE');

      // Include group prefix.
      if ($this->entityType === 'node' && $this->includeGroupPrefix) {
        $substr_limit = strlen($this->entityType) + 2;
        $query->innerJoin('og_membership', 'ogm', "ogm.etid = SUBSTRING(ua.source, {$substr_limit}) AND ogm.entity_type = 'node'");
        $query->innerJoin('purl', 'p', 'p.id = ogm.gid');
        $query->addField('p', 'value', 'group_prefix');
      }
    }

    $query->addField('ua', 'pid', 'rid');
    $query->addField('ua', 'source', 'redirect');
    $query->addField('ua', 'alias', 'source');
    $query->addField('ua', 'language');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);

    $group_prefix = $row->getSourceProperty('group_prefix');
    if (
      !empty($group_prefix) &&
      strpos($row->getSourceProperty('group_prefix'), "$group_prefix/") === FALSE
    ) {
      $row->setSourceProperty(
        'source_with_group_prefix',
        $group_prefix . '/' . $row->getSourceProperty('source')
      );
    }

    return $result;
  }

}
