<?php

namespace Drupal\eic_content\TreeWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;

class TreeWidgetGroupProperty implements TreeWidgetProperties {

  /**
   * {@inheritdoc}
   */
  public function getSortField(): string {
    return 'label';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelFromEntity(EntityInterface $entity): string {
    return $entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function generateExtraCondition(QueryInterface &$query, array $options): void {
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntities(array $ids): array {
    return Group::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function generateSearchQueryResults(string $search_text): array {
    $query = \Drupal::entityQuery('group')
      ->condition('label', $search_text, 'CONTAINS')
      ->range(0, 20);

    if (!empty($ignored_tids)) {
      $query->condition('tid', $ignored_tids, 'NOT IN');
    }

    $entities = $query->execute();
    $entities = Group::loadMultiple($entities);

    return array_map(function (GroupInterface $group) {
      return [
        'name' => $group->label(),
        'tid' => $group->id(),
        'parent' => 0,
      ];
    }, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function formatPreselection(array $entities): string {
    return json_encode(
      array_map(function (EntityInterface $entity) {
        return [
          'name' => $entity->label(),
          'tid' => $entity->id(),
          'parent' => 0,
        ];
      }, $entities)
    );
  }

}
