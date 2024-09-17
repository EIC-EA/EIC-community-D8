<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class ProjectSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class ProjectSourceType extends SourceType {

  use StringTranslationTrait;

  const PROJECT_START_DATE_SOLR_FIELD_ID = 'its_group_project_date_start_value';

  const PROJECT_END_DATE_SOLR_FIELD_ID = 'its_group_project_date_end_value';

  const PROJECT_FUNDING_PROGRAMME_SOLR_FIELD_ID = 'ss_group_project_funding_programme';

  const PROJECT_FIELDS_OF_SCIENCE_SOLR_FIELD_ID = 'ss_group_project_fields_of_science';

  const PROJECT_PARTICIPATING_COUNTRIES_SOLR_FIELD_ID = 'ss_group_project_participating_countries';

  const PROJECT_COORDINATING_COUNTRY_SOLR_FIELD_ID = 'ss_group_project_coordinating_country_code';

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
    return $this->t('Project', [], ['context' => 'eic_search']);
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
      'ss_group_project_status' => $this->t('Project status', [], ['context' => 'eic_search']),
      'ss_project_start_year' => $this->t('Start year', [], ['context' => 'eic_search']),
      'ss_group_project_field_total_cost' => $this->t('Budget Range', [], ['context' => 'eic_search']),
      self::PROJECT_COORDINATING_COUNTRY_SOLR_FIELD_ID => $this->t('Coordinating country', options: ['context' => 'eic_search']),
      self::PROJECT_FUNDING_PROGRAMME_SOLR_FIELD_ID => $this->t('Funding programme', options: ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID => [
        'label' => $this->t('Most active', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Most active', [], ['context' => 'eic_search']),
      ],
      'ss_global_created_date' => [
        'label' => $this->t('Date created', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Date created', [], ['context' => 'eic_search']),
      ],
      'timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recently updated', [], ['context' => 'eic_search']),
      ],
      'ss_global_title' => [
        'label' => $this->t('Group label', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Group label A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Group label Z-A', [], ['context' => 'eic_search']),
      ],
      'ss_group_user_fullname' => [
        'label' => $this->t('Fullname', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Fullname A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Fullname Z-A', [], ['context' => 'eic_search']),
      ],
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_GROUP => [
        'label' => $this->t('Last liked', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last liked', [], ['context' => 'eic_search']),
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
    return [self::PROJECT_START_DATE_SOLR_FIELD_ID, 'DESC'];
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
  public function getPrefilteredTopicsFieldId(): array {
    return ['itm_group_field_vocab_topics'];
  }

  /**
   * @inheritDoc
   */
  public function getLabelFilterMyGroups(): string {
    return $this->t('My projects & content only', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getLabelActiveFilterMyGroups(): string {
    return $this->t('My projects', [], ['context' => 'eic_search']);
  }

}
