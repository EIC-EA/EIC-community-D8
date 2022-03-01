<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class SourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
abstract class SourceType implements SourceTypeInterface {
  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return '';
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return '';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getSecondDefaultSort(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [];
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
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredGroupFieldId(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredTopicsFieldId(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredContentType(): array {
    return [];
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
  public function getLoadMoreBatchItems(): int {
    return SourceTypeInterface::READ_MORE_NUMBER_TO_LOAD;
  }

  /**
   * @inheritDoc
   */
  public function supportDateFilter(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getDateIntervalField(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function prefilterByGroupVisibility(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function ignoreContentFromCurrentUser(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function prefilterByCurrentUser(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorFieldId(): string {
    return '';
  }

  /**
   * @inheritDoc
   */
  public function excludingCurrentGroup(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function prefilterByGroupsMembership(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getLabelFilterMyGroups(): string {
    return $this->t('My groups & content only', [], ['context' => 'eic_search']);
  }

}
