<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show number of content flag likes.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flag_like_content")
 */
class ContentFlagLike extends FieldPluginBase {

  use FlagPluginTrait;

  public function setFlagId() {
    $this->flagId = 'follow_group';
  }

  /**
   * @inheritdoc
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    if ($entity instanceof GroupContent) {
      $entity = $entity->getEntity();
      /** @var \Drupal\node\NodeInterface $entity */

    }
    if ($entity instanceof NodeInterface) {
      return $this->getFlagResults($entity->id(), $entity->getEntityType()->id());
    }
    // N/A means the custom field is not used in a correct view.
    return 'N/A';

  }

}
