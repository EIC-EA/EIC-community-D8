<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\book\BookManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BookManagerInterface $book_manager, RouteMatchInterface $route_match, EICGroupsHelperInterface $eic_groups_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bookManager = $book_manager;
    $this->routeMatch = $route_match;
    $this->eicGroupsHelper = $eic_groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('book.manager'),
      $container->get('current_route_match'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Implements hook_group_insert().
   */
  public function groupInsert(EntityInterface $entity) {
    $this->createGroupWikiBook($entity);
  }

  /**
   * Implements hook_node_view().
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        // Redirect user to first level wiki page if the group book has wiki pages.
        if ($entity->bundle() === 'book') {
          if ($group = $this->eicGroupsHelper->getGroupByEntity($entity)) {
            $data = $this->bookManager->bookTreeAllData($entity->book['bid'], $entity->book, 2);
            $book_data = reset($data);
            if (!empty($book_data['below'])) {
              $wiki_page_nid = reset($book_data['below'])['link']['nid'];
              $wiki_page = $this->entityTypeManager->getStorage('node')->load($wiki_page_nid);
              $redirect_response = new RedirectResponse($wiki_page->toUrl()->toString());
              $redirect_response->send();
            }
            else {
              $build['wiki_section_message'] = [
                '#type' => 'item',
                '#markup' => $this->t('No Wiki pages (yet)'),
              ];
              // Add wiki page create form url to the build array.
              if ($add_wiki_page_url = $this->getWikiPageAddFormUrl($entity, $group)) {
                $build['link_add_wiki_page'] = $add_wiki_page_url->toString();
                $build['link_add_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new wiki page'), $add_wiki_page_url)->toRenderable();
              }
            }
          }
        }
        elseif ($entity->bundle() === 'wiki_page') {
          // Add wiki page create form url to the build array.
          if ($add_wiki_page_url = $this->getWikiPageAddFormUrl($entity)) {
            $build['link_add_wiki_page'] = $add_wiki_page_url->toString();
            $build['link_add_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new wiki page'), $add_wiki_page_url)->toRenderable();
          }
        }
        break;

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
   * Gets wiki page add form Url from the current wiki/book page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity (either a node book or wiki_page).
   * @param bool|\Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   *
   * @return bool|\Drupal\Core\Url
   *   The Url object for the wiki page add form route.
   */
  private function getWikiPageAddFormUrl(EntityInterface $entity, $group = FALSE) {
    if (!($group instanceof GroupInterface)) {
      if (!$group = $this->eicGroupsHelper->getGroupByEntity($entity)) {
        return $group;
      }
    }

    $add_wiki_page_route_parameters = [
      'group' => $group->id(),
      'plugin_id' => 'group_node:wiki_page',
    ];
    $add_wiki_page_link_options = [
      'query' => [
        'parent' => $entity->id(),
      ],
    ];
    $add_wiki_page_url = Url::fromRoute('entity.group_content.create_form', $add_wiki_page_route_parameters, $add_wiki_page_link_options);
    return $add_wiki_page_url;
  }

}
