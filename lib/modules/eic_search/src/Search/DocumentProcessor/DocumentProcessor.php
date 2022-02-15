<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\eic_events\Constants\Event;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

abstract class DocumentProcessor implements DocumentProcessorInterface {

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return TRUE;
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param $key
   * @param $fields
   * @param $value
   */
  protected function addOrUpdateDocumentField(Document &$document, $key, $fields, $value) {
    array_key_exists($key, $fields) ?
      $document->setField($key, $value) :
      $document->addField($key, $value);
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   * @param int $start_date
   * @param int $end_date
   */
  protected function updateEventState(
    Document &$document,
    array $fields,
    int $start_date,
    int $end_date
  ) {
    $now = time();
    // We set a weight value depending the state of the event: 1.ongoing 2.future 3.past
    // so we can sort easily in different overviews.
    // By default we set it as past event
    $weight_event_state = Event::WEIGHT_STATE_PAST;

    if ($now < $start_date) {
      $weight_event_state = Event::WEIGHT_STATE_FUTURE;
    }

    if ($now >= $start_date && $now <= $end_date) {
      $weight_event_state = Event::WEIGHT_STATE_ONGOING;
    }

    $this->addOrUpdateDocumentField(
      $document,
      Event::SOLR_FIELD_ID_WEIGHT_STATE,
      $fields,
      $weight_event_state
    );

    $labels_map = Event::getStateLabelsMapping();

    $this->addOrUpdateDocumentField(
      $document,
      Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL,
      $fields,
      $labels_map[$weight_event_state]
    );
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param $key
   * @param $group
   */
  protected function setGroupOwner(Document &$document, $key, $group) {
    $group_owner = EICGroupsHelper::getGroupOwner($group);
    $document->addField(
      $key,
      $group_owner instanceof UserInterface ? $group_owner->id() : -1
    );
  }

}
