<?php

namespace Drupal\eic_search\Search\Sources\Profile;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_search\Search\Sources\SourceType;

/**
 * Class ContributionSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class ContributionSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['message'];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('Profile - Contribution', [], ['context' => 'eic_search']);
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
      'ss_activity_type' => $this->t('Content type', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topic', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['ss_drupal_timestamp', 'DESC'];
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
    return ['its_global_group_parent_id'];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
      'tm_global_fullname'
    ];
  }

  /**
   * @inheritDoc
   */
  public function allowPagination(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getLoadMoreBatchItems(): int {
    return 10;
  }

  /**
   * @inheritDoc
   */
  public function prefilterByCurrentUser(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorFieldId(): string {
    return 'its_uid';
  }

  /**
   * @inheritDoc
   */
  public function getUniqueId(): string {
    return 'profile-contribution-' . parent::getUniqueId();
  }

}
