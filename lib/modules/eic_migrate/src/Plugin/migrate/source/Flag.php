<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 flags source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_flag",
 *   source_module = "flag"
 * )
 */
class Flag extends FieldableEntity {

  /**
   * Array of included flag types.
   *
   * @var array
   */
  protected $includedFlags = [];

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

    $this->includedFlags = !empty($configuration['constants']['included_flags']) ? $configuration['constants']['included_flags'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('flagging', 'fg');
    $query->fields('fg');

    $query->join('flag', 'fl', 'fg.fid = fl.fid');
    $query->addField('fl', 'name', 'flag_type');
    $query->addField('fl', 'global', 'global');

    // Include only certain flag types.
    if (!empty($this->includedFlags)) {
      $query->condition('fl.name', $this->includedFlags, 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'flagging_id' => $this->t('Flagging ID'),
      'flag_type' => $this->t('Flag type ID'),
      'entity_type' => $this->t('Entity type'),
      'entity_id' => $this->t('Entity ID'),
      'uid' => $this->t('User ID'),
      'sid' => $this->t('Session ID'),
      'global' => $this->t('Flag global state'),
      'timestamp' => $this->t('Created timestamp'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'flagging_id' => [
        'type' => 'integer',
        'alias' => 'fid',
      ],
    ];
  }

}
