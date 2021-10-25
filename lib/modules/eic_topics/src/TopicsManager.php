<?php

namespace Drupal\eic_topics;

/**
 * Class TopicsManager
 */
class TopicsManager {

  const FIELD_ENTITY_TOPICS = 'field_vocab_topics';

  /**
   * @param string $tid
   *
   * @return array
   */
  public function generateTopicsStats(string $tid): array {
    return [
      'stories' => $this->getStatByEntityType($tid, 'node', 'story', ),
      'wiki_page' => $this->getStatByEntityType($tid, 'node', 'wiki_page'),
      'discussion' => $this->getStatByEntityType($tid, 'node', 'discussion'),
      'news' => $this->getStatByEntityType($tid, 'node', 'news'),
      'group' => $this->getStatByEntityType($tid, 'group', 'group'),
      'file' => $this->getStatByEntityType($tid, 'media', 'eic_document'),
      'event' => $this->getStatByEntityType($tid, 'group', 'event'),
      'expert' => 0,
      'organization' => 0,
    ];
  }

  /**
   * @param int $tid
   * @param string $entity_type
   * @param string $bundle
   *
   * @return int
   */
  private function getStatByEntityType(
    int $tid,
    string $entity_type,
    string $bundle
  ): int {
    $query = \Drupal::entityQuery($entity_type)
      ->condition('media' !== $entity_type ? 'type' : 'bundle', $bundle)
      ->condition(self::FIELD_ENTITY_TOPICS, $tid, 'IN');

    return $query->count()->execute();
  }
}
