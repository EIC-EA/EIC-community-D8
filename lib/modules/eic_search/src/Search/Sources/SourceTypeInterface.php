<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Interface SourceTypeInterface
 *
 * @package Drupal\eic_groups\Search\Sources
 */
interface SourceTypeInterface {

  const LAYOUT_COMPACT = 'compact';

  const LAYOUT_COLUMNS = 'columns';

  const LAYOUT_COLUMNS_COMPACT = 'columns_compact';

  const LAYOUT_3_COLUMNS = 'three_columns';

  const LAYOUT_GLOBAL = 'global';

  const SOLR_FIELD_CONTENT_TYPE_ID = 'ss_global_content_type';

  const SOLR_FIELD_GROUP_TYPE_ID = 'ss_global_content_type';

  const READ_MORE_NUMBER_TO_LOAD = 5;

  /**
   * Get the label that will be shown into the admin.
   *
   * @return string
   */
  public function getLabel(): string;

  /**
   * Return machine names of the source.
   *
   * @return array
   */
  public function getSourcesId(): array;

  /**
   * Return the bundle type of the source.
   *
   * @return string
   */
  public function getEntityBundle(): string;

  /**
   * Get available facets of the source.
   *
   * @return array
   */
  public function getAvailableFacets(): array;

  /**
   * Get available sorting options of the source.
   *
   * @return array
   */
  public function getAvailableSortOptions(): array;

  /**
   * Get default sort for an overview, always 2 keys, first : field id, second: direction.
   *
   * @return array
   */
  public function getDefaultSort(): array;

  /**
   * Get second default sort for an overview, always 2 keys, first : field id, second: direction.
   *
   * @return array
   */
  public function getSecondDefaultSort(): array;

  /**
   * Return the fields ID on search API to search for (will be separated by OR
   * condition).
   *
   * @return array
   */
  public function getSearchFieldsId(): array;

  /**
   * Return the overview layout for the source.
   *
   * @return string
   */
  public function getLayoutTheme(): string;

  /**
   * Returns TRUE if the source is able to handle the group pre filtering.
   *
   * @return bool
   */
  public function ableToPrefilteredByGroup(): bool;

  /**
   * If the overview needs to be prefiltered by group ID
   * we need to get fields in SOLR to search IN.
   *
   * @return array
   */
  public function getPrefilteredGroupFieldId(): array;

  /**
   * If the overview needs to be prefiltered by topic we need to get fields in
   * SOLR to search IN.
   *
   * @return array
   */
  public function getPrefilteredTopicsFieldId(): array;

  /**
   * If the overview needs to be prefiltered by content type
   * we need to get field in SOLR to search IN.
   *
   * @return array
   */
  public function getPrefilteredContentType(): array;

  /**
   * Allow pagination, if false, default will be a load more.
   *
   * @return bool
   */
  public function allowPagination(): bool;

  /**
   * Check if Source allow to have a date filter.
   *
   * @return bool
   */
  public function supportDateFilter(): bool;

  /**
   * Return the solr fields id for "from" and "to" date field.
   *
   * @return array
   */
  public function getDateIntervalField(): array;

  /**
   * Prefilter results by the current group visibility type.
   *
   * @return bool
   */
  public function prefilterByGroupVisibility(): bool;

  /**
   * Content will be prefiltered by user_id.
   *
   * @return bool
   */
  public function prefilterByCurrentUser(): bool;

  /**
   * Get the SOLR field for the user_id.
   *
   * @return string
   */
  public function getUserFieldId(): string;

  /**
   * Prefilter results by excluding the current group.
   *
   * @return bool
   */
  public function excludingCurrentGroup(): bool;

}
