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
  public function getSourceId(): string {
    return 'user';
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return t('User', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'user';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_test1',
      'ss_test2',
      'ss_test3',
      'ss_test4',
      'ss_test5',
      'ss_test6',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_group_labsqdqsdel',
      'ss_group_uel',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldId(): string {
    return 'ss_user_label';
  }

}
