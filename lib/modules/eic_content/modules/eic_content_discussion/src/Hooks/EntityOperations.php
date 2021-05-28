<?php

namespace Drupal\eic_content_discussion\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

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
   * Related Contributors of the discussion.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  protected $contributors;

  /**
   * Content Type Discussion.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $discussion;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Implements hook_node_insert().
   */
  public function discussionInsert(NodeInterface $discussion) {
    $this->discussion = $discussion;

    // Get all contributors of CT Discussion.
    $contributorsFieldList = $this->discussion->get('field_related_contributors');
    $this->contributors = $contributorsFieldList->referencedEntities();

    $related_contributorIds = $this->getRelatedContributorIds();

    if (!in_array($this->discussion->getOwnerId(), $related_contributorIds)) {
      $this->addOwnerAsContributor();
    }

    $this->discussion->save();
  }

  /**
   * Helper function to get contributor IDs.
   *
   * @return array
   */
  private function getRelatedContributorIds() {
    $contributorUserIds = [];
    foreach ($this->contributors as $contributorParagraph) {
      $userRef = $contributorParagraph->get('field_user_ref');
      $user = reset($userRef->referencedEntities());
      // If Contributor is not a Platform Member, continue.
      if ($user === FALSE) {
        continue;
      }
      $contributorUserIds[] = intval($user->id());
    }

    return $contributorUserIds;
  }

  /**
   * Helper function to add discussion owner as contributor.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function addOwnerAsContributor() {
    $newContributorParagraph = Paragraph::create([
      'type' => 'contributor',
      'field_user_ref' => [
        'target_id' => $this->discussion->getOwnerId(),
      ],
      'paragraph_view_mode' => 'platform_member',
    ]);
    $newContributorParagraph->save();

    $contributors = [];
    $this->contributors[] = $newContributorParagraph;
    foreach ($this->contributors as $contributorParagraph) {
      $contributors[] = [
        'target_id' => $contributorParagraph->id(),
        'target_revision_id' => $contributorParagraph->getRevisionId(),
      ];
    }

    $this->discussion->set('field_related_contributors', $contributors);
  }
}
