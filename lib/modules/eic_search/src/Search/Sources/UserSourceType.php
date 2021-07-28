<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserSourceType implements SourceTypeInterface {

  use StringTranslationTrait;

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
    return $this->t('User', [], ['context' => 'eic_search']);
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
      'ss_user_last_name' => $this->t('Last name', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_user_first_name' => [
        'label' => $this->t('First name', [], ['context' => 'eic_search']),
        'ASC' => $this->t('First name A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('First name Z-A', [], ['context' => 'eic_search']),
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
