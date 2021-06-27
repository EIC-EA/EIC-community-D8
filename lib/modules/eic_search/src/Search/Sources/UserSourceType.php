<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Class UserSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserSourceType implements SourceTypeInterface {

  /**
   * @inheritDoc
   */
  public static function getSourceId(): string {
    return 'user';
  }

  /**
   * @inheritDoc
   */
  public static function getLabel(): string {
    return t('User', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public static function getEntityBundle(): string {
    return 'user';
  }

  /**
   * @inheritDoc
   */
  public static function getAvailableFacets(): array {
    return [
      'ss_group_topiqsdqdqsqsddqc_name',
      'ss_test2',
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getAvailableSortOptions(): array {
    return [
      'ss_group_labsqdqsdel',
      'ss_group_uel',
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getSearchFieldId(): string {
    return 'ss_group_label';
  }

}
