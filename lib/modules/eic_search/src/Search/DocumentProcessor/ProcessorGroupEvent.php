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
 * Class ProcessorGroupEvent
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGroupEvent extends DocumentProcessor {

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
    $fid = array_key_exists('its_content_teaser_image_fid', $fields) ?
      $fields['its_content_teaser_image_fid'] :
      NULL;

    $teaser_relative = '';

    if ($fid) {
      $image_style = ImageStyle::load('gallery_teaser_crop_160x160');
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
