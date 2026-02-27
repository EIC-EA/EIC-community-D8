<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Document processor for Research Institution groups.
 *
 * Maps taxonomy term reference fields to Solr facet fields for the
 * Research Institution search interface.
 *
 * @package Drupal\eic_search\Search\DocumentProcessor
 */
class ProcessorResearchInstitution extends DocumentProcessor {

  /**
   * {@inheritdoc}
   */
  public function supports(array $fields): bool {
    return ($fields['ss_search_api_datasource'] ?? '') === 'entity:group'
      && ($fields['ss_group_type'] ?? '') === 'research_institution';
  }

  /**
   * {@inheritdoc}
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $group_id = $fields['its_group_id_integer'] ?? NULL;
    if (!$group_id) {
      return;
    }

    $group = Group::load($group_id);
    if (!$group instanceof GroupInterface) {
      return;
    }

    // Map taxonomy term names to Solr facet fields.
    $this->mapTaxonomyField($document, $group, 'field_ri_entity_type', 'ss_ri_entity_type', 'sm_ri_entity_type');
    $this->mapTaxonomyField($document, $group, 'field_ri_key_disciplines', 'ss_ri_key_disciplines', 'sm_ri_key_disciplines');
    $this->mapTaxonomyField($document, $group, 'field_ri_province', 'ss_ri_province', 'sm_ri_province');
    $this->mapTaxonomyField($document, $group, 'field_ri_transparency_level', 'ss_ri_transparency_level', 'sm_ri_transparency_level');
    $this->mapTaxonomyField($document, $group, 'field_ri_is_sanctioned', 'ss_ri_is_sanctioned', 'sm_ri_is_sanctioned');
    $this->mapTaxonomyField($document, $group, 'field_ri_evidence_defense_links', 'ss_ri_evidence_defense_links', 'sm_ri_evidence_defense_links');

    // Set URL for linking.
    $url = $group->toUrl()->toString();
    $document->setField('ss_url', $url);

    $this->mapTaxonomyField($document, $group, 'field_ri_risk_indicators', 'ss_ri_risk_indicators', 'sm_ri_risk_indicators');
  }

  /**
   * Maps a taxonomy reference field to Solr document fields.
   *
   * @param \Solarium\QueryType\Update\Query\Document $document
   *   The Solr document being processed.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $drupal_field
   *   The Drupal field machine name.
   * @param string $solr_single
   *   The Solr field name for single value (ss_ prefix).
   * @param string $solr_multi
   *   The Solr field name for multi value (sm_ prefix).
   */
  protected function mapTaxonomyField(
    Document &$document,
    FieldableEntityInterface $entity,
    string $drupal_field,
    string $solr_single,
    string $solr_multi
  ): void {
    if (!$entity->hasField($drupal_field)) {
      return;
    }

    $field = $entity->get($drupal_field);
    if ($field->isEmpty()) {
      return;
    }

    $terms = $field->referencedEntities();
    $names = array_map(fn($term) => $term->label(), $terms);

    if (!empty($names)) {
      $document->setField($solr_single, reset($names));
      $document->setField($solr_multi, $names);
    }
  }

}
