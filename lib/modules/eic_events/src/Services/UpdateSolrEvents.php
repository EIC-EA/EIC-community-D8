<?php

namespace Drupal\eic_events\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;

/**
 * Class UpdateSolrEvents
 *
 * @package Drupal\eic_events\Services
 */
class UpdateSolrEvents {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var SolrDocumentProcessor
   */
  protected $solrDocumentProcessor;

  /**
   * UpdateSolrEvents constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor $solr_document_processor
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SolrDocumentProcessor $solr_document_processor
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->solrDocumentProcessor = $solr_document_processor;
  }

  /**
   * Update all events (group, event) that are ongoing or in the future.
   * So state of the event in solr are updated (PAST, ON_GOING, FUTURE).
   */
  public function updateSolrEvents() {
    $events_node_id = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('field_date_range.end_value', time(), '>=')
      ->execute();

    $events_groups_id = $this->entityTypeManager->getStorage('group')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('field_date_range.end_value', time(), '>=')
      ->execute();

    $event_nodes = Node::loadMultiple($events_node_id);
    $event_groups = Group::loadMultiple($events_groups_id);

    $this->solrDocumentProcessor->reIndexEntities(array_merge($event_nodes, $event_groups));
  }

}
