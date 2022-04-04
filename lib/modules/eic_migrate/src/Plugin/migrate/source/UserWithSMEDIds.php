<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\user\Plugin\migrate\source\d7\User;

/**
 * Drupal 7 user source from database.
 *
 * Also includes SMED IDs for taxonomy term references.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "eic_d7_user_with_smed_ids",
 *   source_module = "user"
 * )
 */
class UserWithSMEDIds extends User {

  const MAP_USER_ROLES = [
    3 => 'trusted_user',
    4 => 'service_authentication',
    5 => 'administrator',
    6 => 'content_administrator',
  ];

  /**
   * The SMED taxonomy reference fields.
   *
   * @var array
   */
  protected $smedTaxonomyFields;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    $this->smedTaxonomyFields = !empty($configuration['smed_taxonomy_fields']) ? $configuration['smed_taxonomy_fields'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);

    foreach ($this->smedTaxonomyFields as $field) {
      $fieldValues = $row->getSourceProperty($field);
      if (!empty($fieldValues)) {
        $row->setSourceProperty($field, $this->getTaxonomyFieldValuesWithSmedIds($fieldValues));
      }
    }

    $roles = [];
    foreach ($row->getSourceProperty('roles') as $rid) {
      $roles[] = self::MAP_USER_ROLES[$rid];
    }

    // Adds trusted_user role to all users.
    if (empty($row->getSourceProperty('roles'))) {
      $roles = [
        'trusted_user',
      ];
    }

    $row->setSourceProperty('roles', $roles);

    return $result;
  }

  /**
   * Returns the given field values with their SMED ID, if found.
   *
   * @param array $fieldValues
   *   The array of source field values. Should include taxonomy tid.
   *
   * @return array
   *   The array of new source field values. Includes SMED ids if present.
   */
  protected function getTaxonomyFieldValuesWithSmedIds(array $fieldValues): array {
    foreach ($fieldValues as $key => $fieldValue) {
      $dashboardKeyValues = $this->getFieldValues('taxonomy_term', 'c4m_dashboard_key', $fieldValue['tid']);
      if (isset($dashboardKeyValues[0]['value'])) {
        $fieldValue['smed_id'] = $dashboardKeyValues[0]['value'];
      }
      $fieldValues[$key] = $fieldValue;
    }

    return $fieldValues;
  }

}
