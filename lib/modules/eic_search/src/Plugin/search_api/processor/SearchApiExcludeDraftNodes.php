<?php

namespace Drupal\eic_search\Plugin\search_api\processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Excludes 'draft' entities from being indexed.
 *
 * @SearchApiProcessor(
 *   id = "eic_search_exclude_draft_entities",
 *   label = @Translation("Exclude entities in Draft state"),
 *   description = @Translation("Excludes content in draft state from being indexed."),
 *   stages = {
 *     "alter_items" = 0
 *   }
 * )
 */
class SearchApiExcludeDraftNodes extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()->getValue();
      if ($entity instanceof ContentEntityInterface) {
        if ($entity->hasField('moderation_state')
          && $entity->get('moderation_state')->value === EICContentModeration::STATE_DRAFT) {
          unset($items[$item_id]);
        }
      }

    }
  }

}
