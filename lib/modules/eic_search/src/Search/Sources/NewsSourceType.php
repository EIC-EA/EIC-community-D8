<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class NewsSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class NewsSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['node'];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('News', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'news';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'sm_content_field_vocab_topics_string' => $this->t('Topic', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_geo_string' => $this->t('Region & country', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'its_flag_highlight_content' => [
        'label' => $this->t('Highlight', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Highlighted first', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Highlighted last', [], ['context' => 'eic_search']),
      ],
      'ss_drupal_timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Old', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
      ],
      'ss_content_title' => [
        'label' => $this->t('Title', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Title A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Title Z-A', [], ['context' => 'eic_search']),
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
      'score' => [
        'label' => $this->t('Relevance', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Relevance', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSecondDefaultSort(): array {
    return ['ss_global_created_date', 'DESC'];
  }

  /**
   * @inheritDoc
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
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::DEFAULT;
  }

  /**
   * @inheritDoc
   */
  public function ableToPrefilteredByGroup(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredContentType(): array {
    return ['news'];
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredGroupFieldId(): array {
    return ['ss_global_group_parent_id', 'itm_shared_groups'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['its_flag_highlight_content', 'DESC'];
  }

}
