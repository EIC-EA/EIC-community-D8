<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GroupSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GroupSourceType implements SourceTypeInterface {

  use StringTranslationTrait;

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
    return $this->t('Group', [], ['context' => 'eic_search']);
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
      'ss_group_topic_name' => $this->t('Topic', [], ['context' => 'eic_search']),
      'ss_group_label_string' => $this->t('Group label', [], ['context' => 'eic_search']),
      'ss_group_user_fullname' => $this->t('Full name', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Recent', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Old', [], ['context' => 'eic_search']),
      ],
      'ss_group_label_string' => [
        'label' => $this->t('Group label', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Group label A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Group label Z-A', [], ['context' => 'eic_search']),
      ],
      'ss_group_user_fullname' => [
        'label' => $this->t('Fullname', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Fullname A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Fullname Z-A', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'ss_group_label_string',
    ];
  }

}
