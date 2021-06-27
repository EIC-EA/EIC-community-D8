<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Interface SourceTypeInterface
 *
 * @package Drupal\eic_groups\Search\Sources
 */
interface SourceTypeInterface {

  /**
   * Get the label that will be shown into the admin
   *
   * @return string
   */
  public static function getLabel(): string;

  /**
   * Return the machine name of the source
   *
   * @return string
   */
  public static function getSourceId(): string;

  /**
   * Return the bundle type of the source
   *
   * @return string
   */
  public static function getEntityBundle(): string;

  /**
   * Get available facets of the source
   * @return array
   */
  public static function getAvailableFacets(): array;

  /**
   * Get available sorting options of the source
   * @return array
   */
  public static function getAvailableSortOptions(): array;

  /**
   * Return the field ID on search API to search for
   * @return string
   */
  public static function getSearchFieldId(): string;
}
