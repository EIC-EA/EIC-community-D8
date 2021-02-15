<?php

namespace Drupal\eic_statistics;

/**
 * Provides an interface defining Statistics Storage.
 *
 * Stores the number of entities.
 */
interface StatisticsStorageInterface {

  /**
   * Updates the counter state for a given entity and bundle.
   *
   * @param int $value
   *   The value to be set.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @return void
   */
  public function updateEntityCounter($value, $entity_type, $bundle = NULL);

  /**
   * Counts the number of entities of a given entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @return string
   *   Number of entities.
   */
  public function countTotalEntities($entity_type, $bundle = NULL);

  /**
   * Gets the counter statistic for a given entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @return string|null
   *   The counter statistic value.
   */
  public function getEntityCounter($entity_type, $bundle = NULL);

  /**
   * The array of entity types and bundles
   * being tracked for statistics.
   *
   * @return array
   *   The array containing each entity and bundles.
   */
  public static function getTrackedEntities();

}
