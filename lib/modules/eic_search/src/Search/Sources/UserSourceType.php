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
      'ss_user_last_name' => t('Last name', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_user_first_name' => [
        'label' => t('First name', [], ['context' => 'eic_search']),
        'ASC' => t('First name A-Z', [], ['context' => 'eic_search']),
        'DESC' => t('First name Z-A', [], ['context' => 'eic_search']),
      ]
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
