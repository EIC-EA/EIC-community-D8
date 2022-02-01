<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_events\Constants\Event;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class GroupSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GlobalEventSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['group'];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('Global Event', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'global_event';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_group_topic_name' => $this->t('Topic', [], ['context' => 'eic_search']),
      'sm_group_field_location_type' => $this->t('Location', [], ['context' => 'eic_search']),
      Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL => $this->t('Time', [], ['context' => 'eic_search']),
      'ss_group_event_country' => $this->t('Country', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      Event::SOLR_FIELD_ID_WEIGHT_STATE => [
        'label' => $this->t('State (1. ongoing, 2. future, 3. past)', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Ongoing/upcoming', [], ['context' => 'eic_search'])
      ],
      'timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Old', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
      ],
      'ss_group_label_string' => [
        'label' => $this->t('Event name', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Event name A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Event name Z-A', [], ['context' => 'eic_search']),
      ],
      'its_content_field_date_range_start_value' => [
        'label' => $this->t('Event time', [], ['context' => 'eic_search']),
        'ASC' => $this->t('First events in time', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last events in time', [], ['context' => 'eic_search']),
      ],
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT => [
        'label' => $this->t('Last liked', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last liked', [], ['context' => 'eic_search']),
      ],
      'score' => [
        'label' => $this->t('Relevance', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Relevance', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_COMPACT;
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredContentType(): array {
    return ['event'];
  }

  /**
   * @inheritDoc
   */
  public function supportDateFilter(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getDateIntervalField(): array {
    return [
      'from' => GroupEventSourceType::START_DATE_SOLR_FIELD_ID,
      'to' => GroupEventSourceType::END_DATE_SOLR_FIELD_ID,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return [Event::SOLR_FIELD_ID_WEIGHT_STATE, 'ASC'];
  }

}
