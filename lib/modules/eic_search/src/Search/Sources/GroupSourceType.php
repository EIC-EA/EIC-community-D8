<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Class GroupSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GroupSourceType implements SourceTypeInterface {

  /**
   * @inheritDoc
   */
  public static function getSourceId(): string {
    return 'group';
  }

  /**
   * @inheritDoc
   */
  public static function getLabel(): string {
    return t('Group', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public static function getEntityBundle(): string {
    return 'group';
  }

  /**
   * @inheritDoc
   */
  public static function getAvailableFacets(): array {
    return [
      'ss_group_topic_name',
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getAvailableSortOptions(): array {
    return [
      'ss_group_label',
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getSearchFieldId(): string {
    return 'ss_group_label';
  }

}
