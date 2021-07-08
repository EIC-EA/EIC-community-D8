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
      'timestamp' => t('Created date', [], ['context' => 'eic_search']),
      'ss_group_label_string' => t('Group label', [], ['context' => 'eic_search']),
      'ss_group_user_fullname' => t('Fullname', [], ['context' => 'eic_search']),
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
