<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\group\Entity\GroupInterface;
use Drupal\views\ResultRow;


/**
 * Field handler to show number of group flag recommends.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flag_recommend_group")
 */
class GroupFlagRecommend extends FlagPluginBase {


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

    return 'N/A';

  }

}
