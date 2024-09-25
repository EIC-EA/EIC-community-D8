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

    $project_funding = '';
    /** @var \Drupal\taxonomy\Entity\Term $group_project_funding */
    $group_project_funding = $group->get('field_project_funding_programme')->entity;
    if ($group_project_funding instanceof TermInterface) {
      $project_funding = $group_project_funding->label();
    }

    $fields_of_science = $group->get('field_project_fields_of_science')
      ->referencedEntities();
    $fields_of_science_terms = array_map(function(TermInterface $term) {
      $map = [];
      $uuid = $term->uuid();
      if (isset(EIC_TAXONOMY_FIELDS_OF_SCIENCE_TERMS[$uuid])) {
        $map = EIC_TAXONOMY_FIELDS_OF_SCIENCE_TERMS[$uuid];
      }
      return $map;
    }, $fields_of_science);

    $stakeholder_coordinators = [];
    $stakeholder_participants = [];

    /** @var \Drupal\eic_stakeholder\Entity\Stakeholder[] $stakeholder_coord_entities */
    $stakeholder_coord_entities = $group->getContentEntities('group_stakeholder:coordinator');
    foreach ($stakeholder_coord_entities as $stakeholder_coord_entity) {
      $country_code = $stakeholder_coord_entity->get('field_stakeholder_address')
        ->getValue()[0]['country_code'];
      $country_name = (string) \Drupal::service('country_manager')
        ->getList()[$country_code];
      $stakeholder_coordinators[] = [
        'country_code' => $country_code,
        'name' => "$country_name",
      ];
    }

    if ($stakeholder_coord_entities) {
      $coordinator_country_name_facet = (string) \Drupal::service('country_manager')
        ->getList()[$stakeholder_coord_entities[0]->get('field_stakeholder_address')
        ->getValue()[0]['country_code']] ?? '';
    }

    $stakeholder_partic_entities = $group->getContentEntities('group_stakeholder:participant');
    foreach ($stakeholder_partic_entities as $stakeholder_partic_entity) {
      $country_code = $stakeholder_partic_entity?->get('field_stakeholder_address')->first()->getValue()['country_code'];
      if ($country_code) {
        $country_name = (string) \Drupal::service('country_manager')
          ->getList()[$country_code];
        $stakeholder_participants[] = [
          'country_code' => $country_code,
          'name' => $country_name,
        ];
      }
    }

    $this->addOrUpdateDocumentField(
      $document,
      ProjectSourceType::PROJECT_PARTICIPATING_COUNTRIES_SOLR_FIELD_ID,
      $fields,
      json_encode([
        'coordinators' => $stakeholder_coordinators,
        'participants' => $stakeholder_participants,
      ])
    );

    $this->addOrUpdateDocumentField(
      $document,
      ProjectSourceType::PROJECT_COORDINATING_COUNTRY_SOLR_FIELD_ID,
      $fields,
      $coordinator_country_name_facet,
    );

    $total_cost = (float) $group->get('field_project_total_cost')->value;

    $ranges = [
      '0 - 1.000.000' => [
        'min' => 0,
        'max' => 1000000.00,
      ],
      '1.000.000 - 2.000.000' => [
        'min' => 1000000.00,
        'max' => 2000000.00
      ],
      '2.000.000 - 3.000.000' => [
        'min' => 2000000.00,
        'max' => 3000000.00,
      ],
      '3.000.000+' => [
        'min' => 3000000.00,
        'max' => PHP_INT_MAX,
      ]
    ];

    $total_cost_solr_field = 'none';

    foreach ($ranges as $string_val => $range) {
      if ($total_cost > $range['min'] && $total_cost <= $range['max']) {
        $total_cost_solr_field = $string_val;
        break;
      }
    }

    $document->addField('ss_group_project_field_total_cost', $total_cost_solr_field);

    $start_year = explode('-', $fields['ds_group_field_project_date'])[0];
    $document->addField('ss_project_start_year', $start_year);

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
      $project_funding,
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
