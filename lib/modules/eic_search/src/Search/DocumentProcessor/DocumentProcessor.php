<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\eic_events\Constants\Event;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_topics\Constants\Topics;
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
   * @param string|array $topics
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createChildrenTerm($topics, $vid = Topics::TERM_VOCABULARY_TOPICS_ID): array {
    $topics_data = is_array($topics) ?: [$topics];
    $topics = [];

    foreach ($topics_data as $topic) {
      /** @var \Drupal\taxonomy\TermInterface[] $topic */
      $topic = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $topic,
          'vid' => $vid,
        ]);

      $topic = reset($topic);
      $parents = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadParents($topic->id());
      /** @var \Drupal\taxonomy\TermInterface $parent */
      $parent = reset($parents);

      if (!empty($parent)) {
        $topics[] = $topic->getName() . '___' . $parent->getName();
      }
      else {
        $topics[] = $topic->getName();
      }
    }

    return $topics;
  }

}
