<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
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
    $group_parent_label = $fields['ss_global_group_parent_label'] ?? '';
    $group_parent_url = $fields['ss_global_group_parent_url'] ?? '';
    $group_parent_id = $fields['its_global_group_parent_id'] ?? -1;
    $group_is_published = TRUE;
    $group_type = '';
    $datasource = $fields['ss_search_api_datasource'];

    switch ($datasource) {
      case 'entity:node':
        $nid = $fields['its_content_nid'];

        $group_content_ids = \Drupal::entityQuery('group_content')
          ->condition('entity_id', $nid)
          ->condition('type', '%-group_node%', 'LIKE')
          ->range(0, 1)
          ->execute();
        break;
    }

    if (!empty($group_content_ids)) {
      $group_content = GroupContent::load(reset($group_content_ids));
      if ($group_content instanceof GroupContentInterface) {
        $group_entity = $group_content->getGroup();
        $group_parent_label = $group_entity->label();
        $group_parent_url = $group_entity->toUrl()->toString();
        $group_parent_id = $group_entity->id();
        $group_is_published = $group_entity->isPublished();
        $group_type = $group_entity->getGroupType()->id();
      }
    }

    $this->addOrUpdateDocumentField($document, 'ss_global_group_parent_label', $fields, $group_parent_label);
    $this->addOrUpdateDocumentField($document, 'ss_global_group_parent_url', $fields, $group_parent_url);
    $this->addOrUpdateDocumentField($document, 'ss_global_group_parent_type', $fields, $group_type);
    $this->addOrUpdateDocumentField($document, 'its_global_group_parent_id', $fields, $group_parent_id);
    $this->addOrUpdateDocumentField($document, 'its_global_group_parent_published', $fields, (int) $group_is_published);
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    $datasource = $fields['ss_search_api_datasource'];
    return $datasource !== 'entity:group' && $datasource !== 'entity:message';
  }

}
