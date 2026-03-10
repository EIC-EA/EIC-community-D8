<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Research Institution source type for search/filter functionality.
 *
 * Defines search configuration for Research Institution groups including
 * facets, sort options, and Solr field mappings.
 *
 * @package Drupal\eic_search\Search\Sources
 */
final class ResearchInstitutionSourceType extends SourceType {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Research Institutions', [], ['context' => 'eic_search']);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcesId(): array {
    return ['group'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'research_institution';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutTheme(): string {
    return 'rins-overview';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableFacets(): array {
    return [
      'sm_ri_entity_type' => $this->t('Entity type', [], ['context' => 'eic_search']),
      'sm_ri_key_disciplines' => $this->t('Key disciplines', [], ['context' => 'eic_search']),
      'sm_ri_province' => $this->t('Province', [], ['context' => 'eic_search']),
      'sm_ri_transparency_level' => $this->t('Transparency level', [], ['context' => 'eic_search']),
      'sm_ri_is_sanctioned' => $this->t('Is sanctioned entity', [], ['context' => 'eic_search']),
      'sm_ri_evidence_defense_links' => $this->t('Evidence of defense links', [], ['context' => 'eic_search']),
      'sm_ri_risk_indicators' => $this->t('Risk indicators (include)', [], ['context' => 'eic_search']),
      'sm_ri_risk_indicators_exclude' => $this->t('Risk indicators (exclude)', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableSortOptions(): array {
    return [
      'score' => [
        'label' => $this->t('Relevance', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Relevance', [], ['context' => 'eic_search']),
      ],
      'ss_global_title' => [
        'label' => $this->t('Name', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Name (A-Z)', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Name (Z-A)', [], ['context' => 'eic_search']),
      ],
      'ss_drupal_changed_timestamp' => [
        'label' => $this->t('Last Updated', [], ['context' => 'eic_search']),
        'DESC' => $this->t('Recently Updated', [], ['context' => 'eic_search']),
        'ASC' => $this->t('Oldest Updated', [], ['context' => 'eic_search']),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSort(): array {
    return ['score', 'DESC'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondDefaultSort(): array {
    return ['ss_global_title', 'ASC'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchFieldsId(): array {
    return [
      'tm_global_title',
      'tm_X3b_en_rendered_item',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function allowPagination(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoadMoreBatchItems(): int {
    return 20;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId(): string {
    return 'research-institution-' . parent::getUniqueId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefilteredContentType(): array {
    return ['research_institution'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludeFacets(): array {
    return [
      'sm_ri_risk_indicators_exclude' => 'sm_ri_risk_indicators',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiresAuthentication(): bool {
    return TRUE;
  }

}
