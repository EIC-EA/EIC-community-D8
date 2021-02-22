<?php

namespace Drupal\eic_statistics\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\eic_statistics\StatisticsStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Cron.
 *
 * Implementations for hook_cron().
 */
class CronOperations implements ContainerInjectionInterface {

  /**
   * The EIC statistics storage.
   *
   * @var \Drupal\eic_statistics\StatisticsStorageInterface
   */
  protected $statisticsStorage;

  /**
   * Constructs a Cron object.
   *
   * @param \Drupal\eic_statistics\StatisticsStorageInterface $statistics_storage
   *   The EIC statistics storage service.
   */
  public function __construct(StatisticsStorageInterface $statistics_storage) {
    $this->statisticsStorage = $statistics_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_statistics.storage')
    );
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $this->updateEntityCountersStatistic();
  }

  /**
   * Updates counters statistic for each tracked entity.
   */
  public function updateEntityCountersStatistic() {
    foreach ($this->statisticsStorage->getTrackedEntities() as $entity_type => $bundles) {
      // Updates the counter for each entity bundle.
      // We need this condition because the user
      // entity doesn't have any bundles.
      if (is_array($bundles)) {
        foreach ($bundles as $bundle) {
          $count = $this->statisticsStorage->countTotalEntities($entity_type, $bundle);
          $this->statisticsStorage->updateEntityCounter($count, $entity_type, $bundle);
        }
      }
      else {
        $count = $this->statisticsStorage->countTotalEntities($entity_type);
        $this->statisticsStorage->updateEntityCounter($count, $entity_type);
      }
    }
  }

}
