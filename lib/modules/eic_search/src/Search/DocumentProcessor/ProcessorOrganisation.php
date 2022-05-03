<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\paragraphs\ParagraphInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorOrganisation
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorOrganisation extends DocumentProcessor {

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $group = Group::load($fields['its_group_id_integer']);

    if (!$group instanceof GroupInterface) {
      return;
    }

    $locations = $group->get('field_locations')->referencedEntities();
    $locations = array_map(function(ParagraphInterface $paragraph) {
      return $paragraph->get('field_city')->value;
    }, $locations);

    $this->addOrUpdateDocumentField(
      $document,
      'sm_group_field_locations_string',
      $fields,
      $locations
    );
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    $group_type = array_key_exists('ss_group_type', $fields) ?
      $fields['ss_group_type'] :
      NULL;

    return $group_type === 'organisation';
  }

}
