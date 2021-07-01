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
      'ss_user_last_name',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_user_first_name',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'ss_user_first_name',
      'ss_user_last_name',
    ];
  }

}
