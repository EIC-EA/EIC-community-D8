<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\eic_search\Search\Sources\GroupEventSourceType;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorGlobalEvent
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGlobalEvent extends DocumentProcessor {

  /**
   * @var FileUrlGeneratorInterface $urlGenerator
   */
  private $urlGenerator;

  /**
   * @param FileUrlGeneratorInterface $urlGenerator
   */
  public function __construct(FileUrlGeneratorInterface $urlGenerator) {
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $fid = array_key_exists('its_event_teaser_fid', $fields) ?
      $fields['its_event_teaser_fid'] :
      NULL;

    $teaser_relative = '';

    if ($fid) {
      $image_style = ImageStyle::load('large');
      $file = File::load($fid);
      $image_uri = $file->getFileUri();

      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($image_uri));
    }

    $this->addOrUpdateDocumentField(
      $document,
      'ss_event_formatted_image',
      $fields,
      $teaser_relative
    );

    $start_date = new DrupalDateTime($fields['ds_group_field_date_range']);
    $end_date = new DrupalDateTime($fields['ds_group_field_date_range_end_value']);

    $registration_start_date = array_key_exists('ds_event_registration_date_start', $fields) ?
      $fields['ds_event_registration_date_start'] :
      NULL;
    $registration_end_date = array_key_exists('ds_event_registration_date_end', $fields) ?
      $fields['ds_event_registration_date_end'] :
      NULL;

    if ($registration_start_date || $registration_end_date) {
      $registration_start_date = new DrupalDateTime($fields['ds_event_registration_date_start']);
      $registration_end_date = new DrupalDateTime($fields['ds_event_registration_date_end']);

      $this->addOrUpdateDocumentField(
        $document,
        'its_event_registration_date_start',
        $fields,
        $registration_start_date->getTimestamp()
      );

      $this->addOrUpdateDocumentField(
        $document,
        'its_event_registration_date_end',
        $fields,
        $registration_end_date->getTimestamp()
      );
    }

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

    if (array_key_exists('ss_group_country_code', $fields)) {
      $country_code = $fields['ss_group_country_code'];
      $countries = CountryManager::getStandardList();

      $this->addOrUpdateDocumentField(
        $document,
        'ss_group_event_country',
        $fields,
        array_key_exists($country_code, $countries) ? $countries[$country_code] : $country_code
      );
    }
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    $group_type = array_key_exists('ss_group_type', $fields) ?
      $fields['ss_group_type'] :
      NULL;

    return $group_type === 'event';
  }

}
