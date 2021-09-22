<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class GlobalSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class GlobalSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return [
      'group',
      'node',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('Global', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'global';
  }

  /**
   * @inheritDoc
   */
  public function getAvailableFacets(): array {
    return [
      'ss_global_content_type' => $this->t('Content type', [], ['context' => 'eic_search']),
      'ss_group_user_fullname' => $this->t('Full name', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_topics_string' => $this->t('Topics', [], ['context' => 'eic_search']),
      'sm_content_field_vocab_geo_string' => $this->t('Region & country', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_global_created_date' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Old', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
      ],
      'ss_global_title' => [
        'label' => $this->t('Title', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Title A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Title Z-A', [], ['context' => 'eic_search']),
      ],
      'ss_group_user_fullname' => [
        'label' => $this->t('Fullname', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Fullname A-Z', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Fullname Z-A', [], ['context' => 'eic_search']),
      ],
      'its_document_download_total' => [
        'label' => $this->t('Download', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Min download', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Max download', [], ['context' => 'eic_search']),
      ],
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT => [
        'label' => $this->t('Last liked', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Last liked', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_X3b_en_rendered_item',
      'ss_global_title',
      'ss_global_group_parent_label',
      'sm_filename',
      'ss_global_fullname',
    ];
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
    return ['ss_global_group_parent_id', 'its_group_id_integer'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['ss_global_created_date', 'DESC'];
  }

}
