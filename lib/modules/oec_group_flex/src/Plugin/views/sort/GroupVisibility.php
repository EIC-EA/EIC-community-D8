<?php

namespace Drupal\oec_group_flex\Plugin\views\sort;

use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\Views;

/**
 * A handler to provide a sort for group visibility.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsSort("group_visibility")
 */
class GroupVisibility extends SortPluginBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    // Creates new configuration for views join plugin. This will join the
    // group visibility table with groups_field_data table in order to filter
    // the query by the selected group visibilities.
    $definition = [
      'table' => 'oec_group_visibility',
      'field' => 'gid',
      'left_table' => 'groups_field_data',
      'left_field' => 'id',
    ];
    // Instantiates the join plugin.
    $join = Views::pluginManager('join')->createInstance('standard', $definition);
    // Adds the join relationship to the query.
    $query->addRelationship('oec_group_visibility', $join, 'oec_group_visibility');
    // Filters out the query by group visibility.
    $query->addOrderBy('oec_group_visibility', 'type', $this->options['order']);
  }

}
