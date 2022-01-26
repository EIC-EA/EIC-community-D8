<?php

namespace Drupal\eic_content\Hooks;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 */
class EntityOperationsContributor implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Related Contributors of the node.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  protected $contributors;

  /**
   * Node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Constructs a new EntityOperationsContributors object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    Connection $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('database')
    );
  }

  /**
   * Acts on hook_node_view() for node entities.
   *
   * @param array $build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $this->node = $entity;

    // Get all node contributors.
    $contributorsFieldList = $entity->get('field_related_contributors');

    // Add array of contributor IDs to include in the renderable array.
    $contributorIds = [];

    if (!$contributorsFieldList->isEmpty()) {
      $this->contributors = $contributorsFieldList->referencedEntities();
      $contributorIds = $this->getRelatedContributorIds();
    }

    if ($comment_contributorIds = $this->getNodeCommentContributorIds($entity)) {
      foreach ($comment_contributorIds as $comment_contributorId) {
        if (!in_array($comment_contributorId['uid'], $contributorIds)) {
          $contributorIds[] = intval($comment_contributorId['uid']);
        }
      }
    }

    // Add contributor IDs to the renderable array.
    $build['contributor_ids'] = $contributorIds;
  }

  /**
   * Implements hook_entity_presave().
   */
  public function nodePreSave(NodeInterface $node) {
    $this->node = $node;

    if ($node->isNew()) {
      // Get all contributors of CT Discussion.
      $contributorsFieldList = $this->node->get('field_related_contributors');
      $this->contributors = $contributorsFieldList->referencedEntities();

      $related_contributorIds = $this->getRelatedContributorIds();

      if (!in_array($this->node->getOwnerId(), $related_contributorIds)) {
        $this->addOwnerAsContributor();
      }
    }
  }

  /**
   * Helper function to get contributor IDs.
   *
   * @return array
   *   Array of user IDs.
   */
  private function getRelatedContributorIds() {
    $contributorUserIds = [];
    foreach ($this->contributors as $contributorParagraph) {
      $target_entities = $contributorParagraph->get('field_user_ref')
        ->referencedEntities();
      $user = reset($target_entities);
      // If Contributor is not a Platform Member, continue.
      if ($user === FALSE) {
        continue;
      }
      $contributorUserIds[] = intval($user->id());
    }

    return $contributorUserIds;
  }

  /**
   * Helper function to add owner as contributor.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function addOwnerAsContributor() {
    $newContributorParagraph = Paragraph::create([
      'type' => 'contributor',
      'field_user_ref' => [
        'target_id' => $this->node->getOwnerId(),
      ],
      'paragraph_view_mode' => 'platform_member',
    ]);
    $newContributorParagraph->save();

    $contributors = [];
    $contributors[] = $newContributorParagraph;
    foreach ($this->contributors as $contributorParagraph) {
      $contributors[] = [
        'target_id' => $contributorParagraph->id(),
        'target_revision_id' => $contributorParagraph->getRevisionId(),
      ];
    }

    $this->contributors = $contributors;
    $this->node->set('field_related_contributors', $contributors);
  }

  /**
   * Helper function to get contributor IDs from node comments.
   *
   * @return array|bool
   *   Array of user IDs or FALSE if no contributors have been found.
   */
  private function getNodeCommentContributorIds() {
    $query = $this->database->select('comment_field_data', 'c')
      ->fields('c', ['uid']);
    $query->condition('c.entity_id', $this->node->id());
    $query->condition('c.entity_type', 'node');
    // Skip anonymous users.
    $query->condition('c.uid', 0, '<>');
    // Skip contributors with deleted comments.
    $query->join('comment__field_comment_is_soft_deleted', 'csf', 'c.cid = csf.entity_id');
    $query->condition('csf.field_comment_is_soft_deleted_value', FALSE);
    // We group by uid to avoid duplicated results.
    $query->groupBy('c.uid');
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $results ?: FALSE;
  }

}
