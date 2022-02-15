<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\group\Entity\Group;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorGroupContent
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGroupContent extends DocumentProcessor {

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $group_parent_label = '';
    $group_parent_url = '';
    $group_parent_id = -1;
    $group_is_published = TRUE;

    if (array_key_exists('its_content__group_content__entity_id_gid', $fields)) {
      if ($group_entity = Group::load($fields['its_content__group_content__entity_id_gid'])) {
        $group_parent_label = $group_entity->label();
        $group_parent_url = $group_entity->toUrl()->toString();
        $group_parent_id = $group_entity->id();
        $group_is_published = $group_entity->isPublished();
      }
    }

    $document->addField('ss_global_group_parent_label', $group_parent_label);
    $document->addField('ss_global_group_parent_url', $group_parent_url);
    $document->addField('ss_global_group_parent_id', $group_parent_id);
    $this->addOrUpdateDocumentField($document, 'its_global_group_parent_published', $fields, (int) $group_is_published);
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return $fields['ss_search_api_datasource'] !== 'entity:group';
  }

}