<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Search\DocumentProcessor\DocumentProcessorInterface;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class OrganisationSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class OrganisationSourceType extends SourceType {

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
    return $this->t('Organisation', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'organisation';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'sm_group_organisation_type_string' => $this->t('Organisation type', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topic', [], ['context' => 'eic_search']),
      'sm_group_field_locations_string' => $this->t('Locations', [], ['context' => 'eic_search']),
      'sm_organisation_services_products' => $this->t('Activity sectors', [], ['context' => 'eic_search']),
      'sm_organisation_target_market_name' => $this->t('Target sectors', [], ['context' => 'eic_search']),
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
    return [DocumentProcessorInterface::SOLR_MOST_ACTIVE_ID, 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
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
  public function getPrefilteredContentType(): array {
    return ['organisation'];
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
    return $this->t('My organisations & content only', [], ['context' => 'eic_search']);
  }

}
