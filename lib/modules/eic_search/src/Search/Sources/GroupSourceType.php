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
  public function getSourceId(): string {
    return 'group';
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return t('Group', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'group';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_group_topic_name',
      'ss_group_label',
      'ss_group_user_fullname',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'timestamp',
      'ss_group_label',
      'ss_group_user_fullname',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldId(): string {
    return 'ss_group_label';
  }

}
