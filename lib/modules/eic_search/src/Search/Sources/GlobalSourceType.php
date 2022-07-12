<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class GlobalSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GlobalSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return [
      'group',
      'node',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('Global', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'global';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_global_content_type' => $this->t('Content type', [], ['context' => 'eic_search']),
      'ss_group_user_fullname' => $this->t('Full name', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topic', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_geo_string' => $this->t('Regions & countries', [], ['context' => 'eic_search']),
      'ss_content_language_string' => $this->t('Languages', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_global_created_date' => [
        'label' => $this->t('Date created', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Date created', [], ['context' => 'eic_search']),
      ],
      'ss_global_title' => [
        'label' => $this->t('Title', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Title A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Title Z-A', [], ['context' => 'eic_search']),
      ],
      'ss_group_user_fullname' => [
        'label' => $this->t('Fullname', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Fullname A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Fullname Z-A', [], ['context' => 'eic_search']),
      ],
      'its_document_download_total' => [
        'label' => $this->t('Download', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most downloaded', [], ['context' => 'eic_search']),
      ],
      'its_statistics_view' => [
        'label' => $this->t('View', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most viewed', [], ['context' => 'eic_search']),
      ],
      'its_content_comment_count' => [
        'label' => $this->t('Comment count', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most commented', [], ['context' => 'eic_search']),
      ],
      'its_flag_like_content' => [
        'label' => $this->t('Likes', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most liked', [], ['context' => 'eic_search']),
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
  public function getSearchFieldsId(): array {
    return [
      'tm_X3b_en_rendered_item',
      'tm_global_title',
      'ss_global_group_parent_label',
      'sm_filename',
      'ss_global_fullname',
      'tm_X3b_en_saa_field_document_media',
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
    return ['its_global_group_parent_id', 'its_group_id_integer'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['ss_global_created_date', 'DESC'];
  }

}
