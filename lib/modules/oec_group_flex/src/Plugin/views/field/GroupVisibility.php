<?php

namespace Drupal\oec_group_flex\Plugin\views\field;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\group\Entity\GroupInterface;
use Drupal\views\ResultRow;
use Drupal\views\Views;

/**
 * A handler to provide a field for group visibility.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_visibility")
 */
class GroupVisibility extends GroupFlexFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
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
    $alias = $query->addField('oec_group_visibility', 'type');
    $query->addOrderBy('oec_group_visibility', 'type', $order, $alias);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $group = $values->_entity;
    if (!$group instanceof GroupInterface) {
      return '';
    }

    try {
      return $this->oecGroupFlexHelper->getGroupVisibilityTagLabel($group);
    }
    catch (MissingDataException $e) {
      return '';
    }
  }

}
