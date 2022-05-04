<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Drupal 7 URL aliases source from database.
 *
 * @MigrateSource(
 *   id = "eic_d7_flag_taxonomy_term_with_smed_ids",
 *   source_module = "flag"
 * )
 */
class FlagTaxonomyTermWithSmedIds extends Flag {

  /**
   * Array of SMED vocabularies from d7.
   *
   * @var array
   */
  protected $d7SmedVocabularies = [];

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

    $this->d7SmedVocabularies = !empty($configuration['constants']['smed_vocabularies']) ? $configuration['constants']['smed_vocabularies'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->join('taxonomy_term_data', 't', 't.tid = fg.entity_id');

    // Include only certain vocabularies.
    if (!empty($this->d7SmedVocabularies)) {
      $query->join('taxonomy_vocabulary', 'tv', 'tv.vid = t.vid');
      $query->condition('tv.machine_name', $this->d7SmedVocabularies, 'IN');
      $query->addField('tv', 'machine_name', 'vocabulary_machine_name');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['smed_id'] = $this->t('SMED ID');
    $fields['machine_name'] = $this->t('Vocabulary machine name ID');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $term_id = $row->getSourceProperty('entity_id');
    $vocabulary = $row->hasSourceProperty('vocabulary_machine_name') ?
      $row->getSourceProperty('vocabulary_machine_name') :
      NULL;

    if ($vocabulary) {
      switch ($vocabulary) {
        case 'c4m_vocab_event_type':
          $dashboardKeyValues = $this->getFieldValues('taxonomy_term', 'c4m_external_event_type_id', $term_id);
          break;

        default:
          $dashboardKeyValues = $this->getFieldValues('taxonomy_term', 'c4m_dashboard_key', $term_id);
          break;
      }

      $row->setSourceProperty('smed_id', NULL);
      if (isset($dashboardKeyValues[0]['value'])) {
        $row->setSourceProperty('smed_id', $dashboardKeyValues[0]['value']);
      }
    }

    return parent::prepareRow($row);
  }

}
