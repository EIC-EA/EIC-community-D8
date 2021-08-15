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

  const LAYOUT_GLOBAL = 'global';

  const SOLR_FIELD_CONTENT_TYPE_ID = 'ss_global_content_type';

  const READ_MORE_NUMBER_TO_LOAD = 5;

  /**
   * Get the label that will be shown into the admin
   *
   * @return string
   */
  public function getLabel(): string;

  /**
   * Return machine names of the source
   *
   * @return array
   */
  public function getSourcesId(): array;

  /**
   * Return the bundle type of the source
   *
   * @return string
   */
  public function getEntityBundle(): string;

  /**
   * Get available facets of the source
   *
   * @return array
   */
  public function getAvailableFacets(): array;

  /**
   * Get available sorting options of the source
   *
   * @return array
   */
  public function getAvailableSortOptions(): array;

  /**
   * Get default sort for an overview, always 2 keys, first : field id, second: direction
   *
   * @return array
   */
  public function getDefaultSort(): array;

  /**
   * Return the fields ID on search API to search for (will be separated by OR
   * condition)
   *
   * @return array
   */
  public function getSearchFieldsId(): array;

  /**
   * Return the overview layout for the source
   *
   * @return string
   */
  public function getLayoutTheme(): string;

  /**
   * Returns TRUE if the source is able to handle the group pre filtering
   *
   * @return bool
   */
  public function ableToPrefilteredByGroup(): bool;

  /**
   * If the overview needs to be prefiltered by group ID
   * we need to get field in SOLR to search IN
   *
   * @return string
   */
  public function getPrefilteredGroupFieldId(): string;

  /**
   * If the overview needs to be prefiltered by content type
   * we need to get field in SOLR to search IN
   *
   * @return array
   */
  public function getPrefilteredContentType(): array;

  /**
   * Allow pagination, if false, default will be a load more
   *
   * @return bool
   */
  public function allowPagination(): bool;

}
