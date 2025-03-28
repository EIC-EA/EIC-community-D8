<?php

namespace Drupal\eic_comments;

use Drupal\comment\CommentInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldFilteredMarkup;

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
   * Get the number of comments that belong to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param bool $published_only
   *   Whether to count published comments only.
   *
   * @return int
   *   The number of comments that belong to the node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function countEntityComments(EntityInterface $entity, bool $published_only = TRUE) {
    $query = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId());

    if ($published_only) {
      $query->condition('status', CommentInterface::PUBLISHED);
    }

    $query->count();
    $results = $query->execute();
    return (int) $results;
  }

  /**
   * @param string $body
   *
   * @return string
   */
  public static function formatHtmlComment(?string $body): string {
    if (!$body) {
      return '';
    }

    $allowed_tags = array_merge(FieldFilteredMarkup::allowedTags(), ['u', 's']);
    return Xss::filter($body, $allowed_tags);
  }

}
