<?php

namespace Drupal\eic_comments;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service that provides helper functions for comments.
 */
class CommentsHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CommentsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the number of comments that belong to a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return int
   *   The number of comments that belong to the node.
   */
  public function countNodeComments(NodeInterface $node) {
    $query = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $node->id())
      ->count();
    $results = $query->execute();
    return (int) $results;
  }

}
