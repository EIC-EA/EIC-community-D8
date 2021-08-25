<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GroupSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GroupSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['group'];
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
        'ASC' => $this->t('Old', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
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

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_COLUMNS_COMPACT;
  }

  /**
   * @inheritDoc
   */
  public function ableToPrefilteredByGroup(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredGroupFieldId(): string {
    return 'its_group_id_integer';
  }

}
