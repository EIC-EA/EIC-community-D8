<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class DiscussionSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class DiscussionSourceType extends SourceType {

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
    return $this->t('Discussion', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'discussion';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_content_field_discussion_type' => $this->t('Type', [], ['context' => 'eic_search']),
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
      'timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Old', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
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
  public function getSearchFieldsId(): array {
    return [
      'ss_global_title',
      'ss_discussion_last_comment_text',
      'ss_discussion_last_comment_author',
      'ss_global_body_no_html',
      'ss_content_first_name',
      'ss_content_last_name',
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
  public function ableToPrefilteredByGroup(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredContentType(): array {
    return ['discussion'];
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
  public function getDefaultSort(): array {
    return ['its_flag_highlight_content', 'DESC'];
  }

}
