<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class LibrarySourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class LibrarySourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return [
      'node',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('Library', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'library';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_global_content_type' => $this->t('Content type', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topics', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_geo_string' => $this->t('Region & country', [], ['context' => 'eic_search']),
      'ss_content_language_string' => $this->t('Language', [], ['context' => 'eic_search']),
      'ss_content_document_type_string' => $this->t('Document type', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_content_title' => [
        'label' => $this->t('Title', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Title (A-Z)', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Title (Z-A)', [], ['context' => 'eic_search']),
      ],
      'ss_drupal_changed_timestamp' => [
        'label' => $this->t('Last updated', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last updated', [], ['context' => 'eic_search']),
      ],
      'its_flag_highlight_content' => [
        'label' => $this->t('Highlighted files', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Highlighted files', [], ['context' => 'eic_search']),
      ],
      'its_document_download_total' => [
        'label' => $this->t('Number of downloads', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Number of downloads', [], ['context' => 'eic_search']),
      ],
      'its_statistics_view' => [
        'label' => $this->t('Number of views', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Number of views', [], ['context' => 'eic_search']),
      ],
      'its_flag_like_content' => [
        'label' => $this->t('Number of likes', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Number of likes', [], ['context' => 'eic_search']),
      ],
      'ss_global_created_date' => [
        'label' => $this->t('Date uploaded', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Date uploaded', [], ['context' => 'eic_search']),
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
  public function getDefaultSort(): array {
    return ['its_flag_highlight_content', 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getSecondDefaultSort(): array {
    return ['ss_drupal_changed_timestamp', 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
      'tm_X3b_en_saa_field_document_media',
      'sm_filename',
      'tm_X3b_en_content_gallery_slide_name',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_GLOBAL;
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
  public function getPrefilteredGroupFieldId(): array {
    return ['ss_global_group_parent_id', 'itm_shared_groups'];
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredContentType(): array {
    return [
      'gallery',
      'document',
      'video',
    ];
  }

}
