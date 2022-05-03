<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_events\Constants\Event;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Source type for Group events.
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GroupEventSourceType extends SourceType {

  use StringTranslationTrait;

  const START_DATE_SOLR_FIELD_ID = 'its_content_field_date_range_start_value';
  const END_DATE_SOLR_FIELD_ID = 'its_content_field_date_range_end_value';

  /**
   * {@inheritdoc}
   */
  public function getSourcesId(): array {
    return ['node'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Group event', [], ['context' => 'eic_search']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'node_event';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableFacets(): array {
    return [
      'ss_content_event_type_string' => $this->t('Type', [], ['context' => 'eic_search']),
      'sm_content_field_location_type' => $this->t('Location', [], ['context' => 'eic_search']),
      Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL => $this->t('Time', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topic', [], ['context' => 'eic_search']),
      'ss_content_country_code' => $this->t('Country', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableSortOptions(): array {
    return [
      Event::SOLR_FIELD_ID_WEIGHT_STATE => [
        'label' => $this->t('State (1. ongoing, 2. future, 3. past)', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Ongoing/upcoming', [], ['context' => 'eic_search'])
      ],
      'ss_drupal_timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Old', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
      ],
      'ss_content_title' => [
        'label' => $this->t('Event name', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Event name A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Event name Z-A', [], ['context' => 'eic_search']),
      ],
      'its_content_field_date_range_start_value' => [
        'label' => $this->t('Event time', [], ['context' => 'eic_search']),
        'ASC' => $this->t('First events in time', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last events in time', [], ['context' => 'eic_search']),
      ],
      'dm_aggregated_changed' => [
        'label' => $this->t('Last updated', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last updated', [], ['context' => 'eic_search']),
      ],
      'its_last_comment_timestamp' => [
        'label' => $this->t('Last commented', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last commented', [], ['context' => 'eic_search']),
      ],
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT => [
        'label' => $this->t('Last liked', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last liked', [], ['context' => 'eic_search']),
      ],
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::BOOKMARK_CONTENT => [
        'label' => $this->t('Last bookmarked', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last bookmarked', [], ['context' => 'eic_search']),
      ],
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::HIGHLIGHT_CONTENT => [
        'label' => $this->t('Last highlighted', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last highlighted', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
      'ss_global_body_no_html',
      'ss_content_first_name',
      'ss_content_last_name',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_COMPACT;
  }

  /**
   * {@inheritdoc}
   */
  public function ableToPrefilteredByGroup(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefilteredContentType(): array {
    return ['event'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefilteredGroupFieldId(): array {
    return ['its_global_group_parent_id', 'itm_shared_groups'];
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
      'from' => self::START_DATE_SOLR_FIELD_ID,
      'to' => self::END_DATE_SOLR_FIELD_ID,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return [Event::SOLR_FIELD_ID_WEIGHT_STATE, 'ASC'];
  }

}
