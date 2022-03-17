<?php

namespace Drupal\eic_content\TreeWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

interface TreeWidgetProperties {

  public const OPTION_IGNORE_CURRENT_USER = 'ignore_current_user';

  public const OPTION_LOAD_ALL = 'load_all';

  public const OPTION_ITEMS_TO_LOAD = 'items_to_load';

  /**
   * Get the sort field.
   *
   * @return string
   */
  public function getSortField(): string;

  /**
   * Get the label of the entity to show.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Return the label.
   */
  public function getLabelFromEntity(EntityInterface $entity): string;

  /**
   * Add more custom conditions for the target entity.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   Generate extra condition on the current query.
   *
   * @param array $options
   *   Array of options for the query.
   */
  public function generateExtraCondition(QueryInterface &$query, array $options): void;

  /**
   * Return loaded entities by id.
   *
   * @param array $ids
   *   Array of entities id.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Return loaded entities.
   */
  public function loadEntities(array $ids): array;

  /**
   * Generate the query search and return results data.
   *
   * @param string $search_text
   *   The string to search.
   *
   * @return array
   *   Return array of results.
   */
  public function generateSearchQueryResults(string $search_text): array;

  /**
   * Formats the preselection for the widget.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities.
   *
   * @return string
   *   A JSON encoded string.
   */
  public function formatPreselection(array $entities): string;

}
