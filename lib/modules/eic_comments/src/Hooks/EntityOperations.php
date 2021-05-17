<?php

namespace Drupal\eic_comments\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
   * Implements hook_group_insert().
   */
  public function commentInsert(EntityInterface $entity) {
    $commentedEntity = $entity->getCommentedEntity();
    $commentedEntityBundle = $commentedEntity->bundle();

    if ($commentedEntityBundle !== "discussion") {
      return;
    }

    // Get all contributors of CT Discussion.
    $contributorsFieldList = $commentedEntity->get('field_related_contributors');
    $contributorParagraphs = $contributorsFieldList->referencedEntities();

    $contributorUserIds = [];
    foreach ($contributorParagraphs as $contributorParagraph) {
      $userRef = $contributorParagraph->get('field_user_ref');
      $user = reset($userRef->referencedEntities());
      // If Contributor is not a Platform Member, continue.
      if ($user === FALSE) {
        continue;
      }
      $contributorUserIds[] = intval($user->id());
    }

    // If current user is already a Contributor, return.
    $currentUserId = $entity->getOwnerId();
    if (in_array($currentUserId, $contributorUserIds)) {
      return;
    }

    // Create a new Paragraph for Comment Author.
    $newContributorParagraph = Paragraph::create([
      'type' => 'contributor',
      'field_user_ref' => [
        'target_id' => $currentUserId,
      ],
      'paragraph_view_mode' => 'platform_member',
    ]);
    $newContributorParagraph->save();

    // Add newly created Paragraph to already listed Contributors.
    $contributors = [];
    $contributorParagraphs[] = $newContributorParagraph;
    foreach ($contributorParagraphs as $contributorParagraph) {
      $contributors[] = [
        'target_id' => $contributorParagraph->id(),
        'target_revision_id' => $contributorParagraph->getRevisionId(),
      ];
    }

    // Add entire list of Contributors to the Commented Entity.
    $commentedEntity->set('field_related_contributors', $contributors);
    $commentedEntity->save();
  }

}
