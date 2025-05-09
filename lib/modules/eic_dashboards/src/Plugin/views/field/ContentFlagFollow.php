<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\group\Entity\GroupContent;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show number of content flag follows.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flag_follow_content")
 */
class ContentFlagFollow extends FieldPluginBase {

  use FlagPluginTrait;

  public function setFlagId() {
    $this->flagId = 'follow_content';
  }
  /**
   * @inheritdoc
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    if ($entity instanceof GroupContent) {
      $entity = $entity->getEntity();
      /** @var \Drupal\node\NodeInterface $entity */

      return $this->getFlagResults(
        $entity->id(),
        $entity->getEntityType()->id()
      );
    }
    return 'N/A';
  }

}
