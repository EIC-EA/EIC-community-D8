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

  /**
   * Get the label that will be shown into the admin
   *
   * @return string
   */
  public function getLabel(): string;

  /**
   * Return the machine name of the source
   *
   * @return string
   */
  public function getSourceId(): string;

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

}
