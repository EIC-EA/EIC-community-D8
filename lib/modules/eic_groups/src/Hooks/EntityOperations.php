<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  public function groupInsert(EntityInterface $entity) {
    $this->createGroupWikiBook($entity);
  }

  /**
   * Creates top level book page for group wiki section.
   */
  public function createGroupWikiBook(GroupInterface $entity) {
    $node_values = [
      'title' => "{$entity->label()} - Wiki",
      'type' => 'book',
      'uid' => $entity->getOwnerId(),
      'status' => 1,
      'langcode' => $entity->language()->getId(),
      'book' => [
        'bid' => 'new',
        'plid' => 0,
      ],
    ];
    $node = $this->entityTypeManager->getStorage('node')->create($node_values);
    $node->save();
    $entity->addContent($node, 'group_node:book');
  }

}
