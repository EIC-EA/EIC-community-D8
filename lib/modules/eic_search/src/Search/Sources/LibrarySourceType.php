<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

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
      'ss_global_created_date' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Recent', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Old', [], ['context' => 'eic_search']),
      ],
      'ss_global_title' => [
        'label' => $this->t('Title', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Title A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Title Z-A', [], ['context' => 'eic_search']),
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
  public function getSearchFieldsId(): array {
    return [
      'ss_global_title'
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
  public function getPrefilteredGroupFieldId(): string {
    return 'ss_global_group_parent_id';
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
