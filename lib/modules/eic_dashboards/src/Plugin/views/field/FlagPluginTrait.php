<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


trait FlagPluginTrait {

  public string $flagId = '';

  abstract public function setFlagId();

  public function getFlagId() {
    return $this->flagId;
  }

  /**
   * @inheritDoc
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Get the number of flags per entity type, ID and flag_id
   *
   * @return string
   *   The count of flags for that entity and flag_id or N/A if none is found.
   */
  public function getFlagResults($entity_id, $entity_type): string {

    $this->setFlagId();

    $query = \Drupal::database()->select('flag_counts', 'fc');
    $query->addField('fc', 'count');
    $query->condition('fc.entity_type', $entity_type)
      ->condition('fc.entity_id', $entity_id)
      ->condition('fc.flag_id', $this->getFlagId());

    $results = $query->execute()->fetchAssoc();
    if (!empty($results)) {
      return $results['count'];
    }
    return 'N/A';

  }

}
