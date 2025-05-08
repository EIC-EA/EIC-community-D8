<?php

namespace Drupal\eic_dashboards\Plugin\views\field;


use Drupal\group\Entity\GroupContent;
use Drupal\views\ResultRow;

/**
 * Field handler to show number of content flag likes.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("flag_like_content")
 */
class ContentFlagLike extends FlagPluginBase {

  /**
   * @inheritdoc
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    if ($entity instanceof GroupContent) {
      $entity = $entity->getEntity();
      /** @var \Drupal\node\NodeInterface $entity */

      return $this->getFlagResults($entity->id(), $entity->getEntityType()->id());
    }
    return 'N/A';

  }

}
