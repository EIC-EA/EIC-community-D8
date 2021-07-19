<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Class UserGallerySourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserGallerySourceType implements SourceTypeInterface {

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
    return t('User gallery', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'user_gallery';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'sm_user_profile_topic_expertise_string' => t('Topic expertise', [], ['context' => 'eic_search']),
      'sm_user_profile_job_string' => t('Job title', [], ['context' => 'eic_search']),
      'sm_user_profile_field_vocab_topic_interest_array' => t('Topic interest', [], ['context' => 'eic_search']),
      'sm_user_profile_geo_string' => t('Geo interest', [], ['context' => 'eic_search']),
      'ss_user_profile_field_location_address_country_code' => t('Country', [], ['context' => 'eic_search']),
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

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_COLUMNS;
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
    return 'itm_user__group_content__uid_gid';
  }

}
