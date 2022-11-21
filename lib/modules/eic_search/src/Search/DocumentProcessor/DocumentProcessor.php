<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\State\StateInterface;
use Drupal\eic_events\Constants\Event;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_statistics\StatisticsHelper;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

abstract class DocumentProcessor implements DocumentProcessorInterface {

  /**
   * The state cache.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Setter method to inject the SolrDocumentProcessor.
   *
   * @param \Drupal\Core\State\StateInterface|null $solr_document_processor
   *   The state cache.
   */
  public function setStateCache(?StateInterface $state) {
    $this->state = $state;
  }

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
    $current_fields = $document->getFields();

    array_key_exists($key, $current_fields) ?
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

  /**
   * Sanitizes the given string.
   *
   * @param string $text
   *   The text to be sanitized.
   *
   * @return string
   *   The sanitized string.
   */
  public function sanitizeFulltextString(string $text) {
    // Remove inline image src attribute (base64 encoded).
    $text = preg_replace('#(src=".*image/[^;"]+;base64,.*")#iU', '', $text);
    $text = preg_replace('#(src=".*image/[^%3B"]+%3Bbase64%2C.*")#iU', '', $text);
    return $text;
  }

  /**
   * {@inerhitdoc}
   */
  public function postProcess(Document $document, array $fields): bool {
    // The node view counter is not re-indexed everytime we view a node page.
    // Therefore, there is a cron responsible for re-indexing the nodes at a
    // later stage. Here we reset the state cache used in the cron so that we
    // avoid re-indexing the entity multiple times.
    if (isset($fields['its_content_nid'])) {
      $nid = $fields['its_content_nid'];
      $node_view_counter_state_cache = $this->state->get(StatisticsHelper::NODE_VIEW_COUNTER_REINDEX_STATE_CACHE, []);
      if (empty($node_view_counter_state_cache)) {
        return FALSE;
      }

      $key = array_search($nid, $node_view_counter_state_cache);
      if ($key !== FALSE) {
        unset($node_view_counter_state_cache[$key]);
        $this->state->set(StatisticsHelper::NODE_VIEW_COUNTER_REINDEX_STATE_CACHE, $node_view_counter_state_cache);
        return TRUE;
      }
    }
    return FALSE;
  }

}
