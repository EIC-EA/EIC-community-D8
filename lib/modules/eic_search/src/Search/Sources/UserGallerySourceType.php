<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface;

/**
 * Class UserGallerySourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserGallerySourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * This is used as a token for the team sort machine name.
   */
  const TEAM_DEFAULT_SORT = 'team_default_sort';

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
    return $this->t('User gallery', [], ['context' => 'eic_search']);
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
      'sm_user_profile_topic_expertise_string' => $this->t('Topic expertise', [], ['context' => 'eic_search']),
      'sm_user_profile_job_string' => $this->t('Job title', [], ['context' => 'eic_search']),
      'sm_user_profile_field_vocab_topic_interest_array' => $this->t('Topic interest', [], ['context' => 'eic_search']),
      'sm_user_profile_geo_string' => $this->t('Geo interest', [], ['context' => 'eic_search']),
      'ss_user_profile_field_location_address_country_code' => $this->t('Country', [], ['context' => 'eic_search']),
      'sm_user_profile_field_vocab_language_array' => $this->t('Language', [], ['context' => 'eic_search']),
      DocumentProcessorInterface::SOLR_GROUP_ROLES => $this->t('Roles', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      self::TEAM_DEFAULT_SORT => [
        'label' => $this->t('Organisation team default sort', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Default', [], ['context' => 'eic_search']),
      ],
      DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID => [
        'label' => $this->t('Most active', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most active', [], ['context' => 'eic_search']),
      ],
      DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID_GROUP => [
        'label' => $this->t('Most active (in group)', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most active', [], ['context' => 'eic_search']),
      ],
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
  public function getDefaultSort(): array {
    return ['team_default_sort', 'ASC'];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return ['tm_global_fullname'];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_3_COLUMNS;
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
    return ['itm_user__group_content__uid_gid', 'itm_shared_groups'];
  }

  /**
   * @inheritDoc
   */
  public function getAuthorFieldId(): string {
    return 'its_user_id';
  }

  /**
   * @inheritDoc
   */
  public function ignoreAnonymousUser(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueId(): string {
    return 'user-gallery-' . parent::getUniqueId();
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredTopicsFieldId(): array {
    return ['itm_user_profile_field_vocab_topic_expertise'];
  }

}
