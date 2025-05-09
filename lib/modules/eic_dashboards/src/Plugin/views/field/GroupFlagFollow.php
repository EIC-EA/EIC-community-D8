<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\group\Entity\GroupInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;


/**
 * Field handler to show number of group flag follows.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flag_follow_group")
 */
class GroupFlagFollow extends FieldPluginBase {

  use FlagPluginTrait;

  public function setFlagId() {
    $this->flagId = 'follow_group';
  }

  /**
   * @inheritdoc
   */
  public function render(ResultRow $values) {
    if ($values->_entity instanceof GroupInterface) {
      return $this->getFlagResults(
        $values->_entity->id(),
        $values->_entity->getEntityType()->id()
      );
    }
    // N/A means the custom field is not used in a correct view.
    return 'N/A';
  }

}
