<?php

namespace Drupal\eic_group_statistics;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * Provides a service class to re-index group statistics in Search API index.
 */
class GroupStatisticsSearchApiReindex {

  public const DATASOURCE_GROUP = 'entity:group';
  public const DATASOURCE_NODE = 'entity:node';

  /**
   * The Group statistics storage service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsStorageInterface
   */
  protected $groupStatisticsStorage;

  /**
   * Constructs a GroupStatisticsSearchApiReindex object.
   *
   * @param \Drupal\eic_group_statistics\GroupStatisticsStorageInterface $group_statistics_storage
   *   The Group statistics storage service.
   */
  public function __construct(GroupStatisticsStorageInterface $group_statistics_storage) {
    $this->groupStatisticsStorage = $group_statistics_storage;
  }

  /**
   * Re-index group statistics for a given entity.
   *
   * @param ContentEntityInterface $entity
   *   The content entity object.
   */
  public function reindexItem(ContentEntityInterface $entity, $datasource_id = 'entity:group') {
    $indexes = ContentEntity::getIndexesForEntity($entity);

    $updated_item_ids = $entity->getTranslationLanguages();
    foreach (array_keys($updated_item_ids) as $langcode) {
      $inserted_item_ids[] = $langcode;
    }

    $entity_id = $entity->id();

    $combine_id = function ($langcode) use ($entity_id) {
      return $entity_id . ':' . $langcode;
    };

    $updated_item_ids = array_map($combine_id, array_keys($updated_item_ids));
    foreach ($indexes as $index) {
      if ($updated_item_ids) {
        $index->trackItemsUpdated($datasource_id, $updated_item_ids);
      }
    }
  }

}
