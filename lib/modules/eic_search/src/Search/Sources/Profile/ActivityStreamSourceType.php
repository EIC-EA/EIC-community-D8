<?php

namespace Drupal\eic_search\Search\Sources\Profile;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_search\Search\Sources\SourceType;

/**
 * Class ActivityStreamSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class ActivityStreamSourceType extends SourceType {

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
    return $this->t('Profile - Activity stream', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'activity_stream';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_global_content_type' => $this->t('Content type', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topics', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['ds_created', 'DESC'];
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
    return ['ss_global_group_parent_id'];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_X3b_en_rendered_item',
      'tm_global_title',
      'ss_global_group_parent_label',
      'ss_global_fullname'
    ];
  }

  /**
   * @inheritDoc
   */
  public function allowPagination(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function ignoreContentFromCurrentUser(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorFieldId(): string {
    return 'its_content_uid';
  }

}
