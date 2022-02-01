<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class NewsStorySourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class NewsStorySourceType extends SourceType {

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
    return $this->t('News & Stories', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'story';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_content_type' => $this->t('Type', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topic', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_geo_string' => $this->t('Regions & countries', [], ['context' => 'eic_search']),
      'bs_content_is_private' => $this->t('Visibility', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_drupal_changed_timestamp' => [
        'label' => $this->t('Last updated', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recently updated', [], ['context' => 'eic_search']),
      ],
      'ss_drupal_timestamp' => [
        'label' => $this->t('Created date', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most recent', [], ['context' => 'eic_search']),
      ],
      'tm_global_title' => [
        'label' => $this->t('Title', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Title A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Title Z-A', [], ['context' => 'eic_search']),
      ],
      'its_statistics_view' => [
        'label' => $this->t('Views', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most viewed', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Less viewed', [], ['context' => 'eic_search']),
      ],
      'its_flag_like_content' => [
        'label' => $this->t('Likes', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most liked', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Less liked', [], ['context' => 'eic_search']),
      ],
      'its_last_comment_timestamp' => [
        'label' => $this->t('Last commented', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last commented', [], ['context' => 'eic_search']),
      ],
      'its_content_comment_count' => [
        'label' => $this->t('Total comment', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most commented', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Less commented', [], ['context' => 'eic_search']),
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
      'tm_global_title',
      'tm_X3b_en_rendered_item',
      'ss_content_first_name',
      'ss_content_last_name',
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
  public function getPrefilteredContentType(): array {
    return ['story', 'news'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['ss_drupal_timestamp', 'DESC'];
  }

}
