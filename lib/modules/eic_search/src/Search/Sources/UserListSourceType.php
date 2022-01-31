<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserListSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserListSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['user'];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('User list', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'user_list';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'sm_user_profile_topic_expertise_string' => $this->t('Topic expertise', [], ['context' => 'eic_search']),
      'sm_user_profile_job_string' => $this->t('Job title', [], ['context' => 'eic_search']),
      'sm_user_profile_field_vocab_topic_interest_array' => $this->t('Topic interest', [], ['context' => 'eic_search']),
      'sm_user_profile_geo_string' => $this->t('Geo interest', [], ['context' => 'eic_search']),
      'ss_user_profile_field_location_address_country_code' => $this->t('Country', [], ['context' => 'eic_search']),
      'sm_user_profile_field_vocab_language_array' => $this->t('Language', [], ['context' => 'eic_search']),
      'sm_user_profile_role_array' => $this->t('Roles', [], ['context' => 'eic_search']),
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
      ],
      'ds_user_access' => [
        'label' => $this->t('Last active', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last active', [], ['context' => 'eic_search']),
      ],
      'score' => [
        'label' => $this->t('Relevance', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Relevance', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return ['ss_global_fullname'];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_GLOBAL;
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
  public function getPrefilteredGroupFieldId(): array {
    return ['itm_user__group_content__uid_gid'];
  }

}
