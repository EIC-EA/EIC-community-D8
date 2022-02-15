<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Locale\CountryManager;
use Drupal\eic_search\Search\Sources\GroupEventSourceType;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorGroupEvent
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGroupEvent extends DocumentProcessor {

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $start_date = new DrupalDateTime($fields['ds_content_field_date_range']);
    $end_date = new DrupalDateTime($fields['ds_content_field_date_range_end_value']);

    $this->addOrUpdateDocumentField(
      $document,
      GroupEventSourceType::START_DATE_SOLR_FIELD_ID,
      $fields,
      $start_date->getTimestamp()
    );

    $this->addOrUpdateDocumentField(
      $document,
      GroupEventSourceType::END_DATE_SOLR_FIELD_ID,
      $fields,
      $end_date->getTimestamp()
    );

    $this->updateEventState(
      $document,
      $fields,
      $start_date->getTimestamp(),
      $end_date->getTimestamp()
    );

    if (array_key_exists('ss_content_country_code', $fields)) {
      $country_code = $fields['ss_content_country_code'];
      $countries = CountryManager::getStandardList();

      $this->addOrUpdateDocumentField(
        $document,
        'ss_content_country_code',
        $fields,
        array_key_exists($country_code, $countries) ? $countries[$country_code] : $country_code
      );
    }
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return 'entity:node' === $fields['ss_search_api_datasource'] && 'event' === $fields['ss_content_type'];
  }

}
