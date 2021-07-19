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
      'ss_group_topic_name' => t('Topic', [], ['context' => 'eic_search']),
      'ss_group_label_string' => t('Group label', [], ['context' => 'eic_search']),
      'ss_group_user_fullname' => t('Full name', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'timestamp' => [
        'label' => t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => t('Recent', [], ['context' => 'eic_search']),
        'DESC' => t('Old', [], ['context' => 'eic_search']),
      ],
      'ss_group_label_string' => [
        'label' => t('Group label', [], ['context' => 'eic_search']),
        'ASC' => t('Group label A-Z', [], ['context' => 'eic_search']),
        'DESC' => t('Group label Z-A', [], ['context' => 'eic_search']),
      ],
      'ss_group_user_fullname' => [
        'label' => t('Fullname', [], ['context' => 'eic_search']),
        'ASC' => t('Fullname A-Z', [], ['context' => 'eic_search']),
        'DESC' => t('Fullname Z-A', [], ['context' => 'eic_search']),
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
    return self::LAYOUT_COMPACT;
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
