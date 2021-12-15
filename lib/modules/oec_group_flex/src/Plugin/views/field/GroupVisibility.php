<?php

namespace Drupal\oec_group_flex\Plugin\views\field;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\group\Entity\GroupInterface;
use Drupal\views\ResultRow;

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
