<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\eic_search\Search\Sources\ProjectSourceType;
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
      json_encode([])
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
