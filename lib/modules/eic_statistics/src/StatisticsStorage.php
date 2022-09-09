<?php

namespace Drupal\eic_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides the default storage backend for EIC statistics.
 */
class StatisticsStorage implements StatisticsStorageInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a StatisticsStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityCounter($value, $entity_type, $bundle = NULL) {
    $state_key = $this->getEntityCounterStateKey($entity_type, $bundle);
    $this->state->set($state_key, $value);
    $this->invalidateEntityCounterCacheTag($this->getEntityCounterCacheTag($entity_type, $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityCounter($entity_type, $bundle = NULL) {
    $state_key = $this->getEntityCounterStateKey($entity_type, $bundle);
    $this->state->delete($state_key);
    $this->invalidateEntityCounterCacheTag($this->getEntityCounterCacheTag($entity_type, $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public function countTotalEntities($entity_type, $bundle = NULL) {
    if ($this->entityTypeManager->hasDefinition($entity_type)) {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $query = $entity_storage->getQuery();
      // Disable access check on this query.
      $query->accessCheck(FALSE);
      // Add status condition for node and user entities.
      switch ($entity_type) {
        case 'group':
        case 'node':
        case 'user':
          $query->condition('status', TRUE);
          break;
      }

      if ($bundle != NULL) {
        $query->condition('type', $bundle);
      }

      return $query->count()->execute();
    }

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounterStateKey($entity_type, $bundle = NULL) {
    return $bundle == NULL ? "eic_statistics:counter:{$entity_type}" : "eic_statistics:counter:{$entity_type}:{$bundle}";
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounter($entity_type, $bundle = NULL) {
    return $this->state->get($this->getEntityCounterStateKey($entity_type, $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public static function getTrackedEntities() {
    return [
      'group' => ['event', 'group', 'organisation', 'project'],
      'node' => ['challenge'],
      'user' => 'user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounterCacheTag($entity_type, $bundle = NULL) {
    return $bundle == NULL ? "eic_statistics:counter:{$entity_type}" : "eic_statistics:counter:{$entity_type}:{$bundle}";
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateEntityCounterCacheTag($cache_tag) {
    Cache::invalidateTags([$cache_tag]);
  }

}
