<?php

namespace Drupal\eic_search\Search\Sources\UserProfile;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_search\Search\Sources\SourceType;
use Drupal\eic_search\Service\SolrDocumentProcessor;

/**
 * Class MyGroupsSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class MyGroupsSourceType extends SourceType {

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
    return $this->t('User profile - My groups', [], ['context' => 'eic_search']);
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
      'ss_group_visibility_label' => $this->t('Visibility', [], ['context' => 'eic_search']),
      'sm_group_topic_name' => $this->t('Topic', [], ['context' => 'eic_search']),
      'ss_group_field_vocab_geo_string' => $this->t('Region & countries', [], ['context' => 'eic_search']),
      'ss_group_user_fullname' => $this->t('Full name', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAvailableSortOptions(): array {
    return [
      'ss_global_created_date' => [
        'label' => $this->t('Date created', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Date created', [], ['context' => 'eic_search']),
      ],
      'timestamp' => [
        'label' => $this->t('Timestamp', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recent', [], ['context' => 'eic_search']),
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
      'its_' . SolrDocumentProcessor::LAST_FLAGGED_KEY . '_' . FlagType::LIKE_CONTENT => [
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
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
      'tm_global_fullname',
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
    return ['group'];
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
  public function getDefaultSort(): array {
    return ['timestamp', 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getUniqueId(): string {
    return 'user-profile-' . parent::getUniqueId();
  }

}
