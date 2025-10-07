<?php

namespace Drupal\eic_search\Search\Sources\UserProfile;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_search\Search\Sources\SourceType;
use Drupal\eic_search\Search\Sources\ProjectSourceType;

/**
 * Class MyProjectsSourceType
 *
 * @package Drupal\eic_groups\Search\Sources\UserProfile
 */
class MyProjectsSourceType extends SourceType {

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
    return $this->t('User profile - My organisations', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'project';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'sm_group_project_fields_of_science_string' => $this->t('Fields of science', [], ['context' => 'eic_search']),
      ProjectSourceType::PROJECT_FUNDING_PROGRAMME_SOLR_FIELD_ID => $this->t('Funding programme', options: ['context' => 'eic_search']),
      ProjectSourceType::PROJECT_COORDINATING_COUNTRY_SOLR_FIELD_ID => $this->t('Coordinating country', options: ['context' => 'eic_search']),
      'ss_group_project_status' => $this->t('Project status', [], ['context' => 'eic_search']),
      'ss_group_project_field_total_cost' => $this->t('Budget Range', [], ['context' => 'eic_search']),
      'ss_project_start_year' => $this->t('Start year', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return [ProjectSourceType::PROJECT_START_DATE_SOLR_FIELD_ID, 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
      'tm_X3b_en_group_project_teaser',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::DEFAULT;
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
  public function getPrefilteredContentType(): array {
    return ['project'];
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredGroupFieldId(): array {
    return ['its_group_id_integer'];
  }

  /**
   * @inheritDoc
   */
  public function prefilterByGroupsMembership(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function prefilterByUserFromRoute(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueId(): string {
    return 'user-profile-' . parent::getUniqueId();
  }

}
