<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\book\BookManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

  protected $bookManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BookManagerInterface $book_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('book.manager'),
    );
  }

  /**
   * Implements hook_group_insert().
   */
  public function groupInsert(EntityInterface $entity) {
    $this->createGroupWikiBook($entity);
  }

  /**
   * Implements hook_node_access().
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    // Redirect user to first level wiki page if the group book has wiki pages.
    if (\Drupal::routeMatch()->getRouteName() === 'entity.node.canonical' && $entity->bundle() === 'book') {
      if ($this->getGroupByEntity($entity)) {
        $data = $this->bookManager->bookTreeAllData($entity->book['bid'], $entity->book, 2);
        $book_data = reset($data);
        if (!empty($book_data['below'])) {
          $wiki_page_nid = reset($book_data['below'])['link']['nid'];
          $wiki_page = $this->entityTypeManager->getStorage('node')->load($wiki_page_nid);
          $redirect_response = new RedirectResponse($wiki_page->toUrl()->toString());
          $redirect_response->send();
        }
      }
    }
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

  /**
   * Get Group of a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getGroupByEntity(EntityInterface $entity) {
    $group = FALSE;
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    elseif ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_content = GroupContent::loadByEntity($entity);
      // Assuming that the content can be related only to 1 group.
      $group_content = reset($group_content);
      if (!empty($group_content)) {
        $group = $group_content->getGroup();
      }
    }
    return $group;
  }

}
