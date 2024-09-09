<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\eic_search\Search\Sources\ProjectSourceType;
use Drupal\taxonomy\TermInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorProject
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorProject extends DocumentProcessor {

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $group = Group::load($fields['its_group_id_integer']);

    if (!$group instanceof GroupInterface) {
      return;
    }

    $start_date = new DrupalDateTime($fields['ds_group_field_project_date']);
    $end_date = new DrupalDateTime($fields['ds_group_field_project_date_end_value']);

    $group_project_funding = $group->get('field_project_funding_programme')->getValue()[0];
    $project_funding = [
      'url' => $group_project_funding['uri'],
      'title' => $group_project_funding['title'] ?? '',
    ];

    $fields_of_science = $group->get('field_project_fields_of_science')->referencedEntities();
    $fields_of_science_terms = array_map(function(TermInterface $term) {
      $map = [];
      $uuid = $term->uuid();
      if (isset(EIC_TAXONOMY_FIELDS_OF_SCIENCE_TERMS[$uuid])) {
        $map =  EIC_TAXONOMY_FIELDS_OF_SCIENCE_TERMS[$uuid];
      }
      return $map;
    }, $fields_of_science);

    $document->addField('ss_project_cordis_url', EIC_TAXONOMY_CORDIS_BASE_URL . $fields['its_project_grant_agreement_id']);

    $this->addOrUpdateDocumentField(
      $document,
      ProjectSourceType::PROJECT_START_DATE_SOLR_FIELD_ID,
      $fields,
      $start_date->getTimestamp()
    );

    $this->addOrUpdateDocumentField(
      $document,
      ProjectSourceType::PROJECT_END_DATE_SOLR_FIELD_ID,
      $fields,
      $end_date->getTimestamp()
    );

    $this->addOrUpdateDocumentField(
      $document,
      ProjectSourceType::PROJECT_FUNDING_PROGRAMME_SOLR_FIELD_ID,
      $fields,
      json_encode($project_funding)
    );

    $this->addOrUpdateDocumentField(
      $document,
      ProjectSourceType::PROJECT_FIELDS_OF_SCIENCE_SOLR_FIELD_ID,
      $fields,
      json_encode($fields_of_science_terms)
    );

  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    $group_type = array_key_exists('ss_group_type', $fields) ?
      $fields['ss_group_type'] :
      NULL;

    return $group_type === 'project';
  }

}
