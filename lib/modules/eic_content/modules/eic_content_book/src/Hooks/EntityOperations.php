<?php

namespace Drupal\eic_content_book\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Implements hook_node_view().
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        if ($entity->bundle() === 'book') {
          // If book belongs to a group.
          if ($this->moduleHandler->moduleExists('group_content') && $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity)) {
            return;
          }
          // Unsets book navigation since we already have that show in the
          // eic_content_book_navigation block plugin.
          unset($build['book_navigation']);

          $add_book_page_urls = $this->getBookPageAddFormUrls($entity);

          // If user can't access the route.
          if (!$add_book_page_urls['add_child_book_page']->access($this->currentUser)) {
            return;
          }

          if (isset($entity->book) && $entity->book['pid'] === '0') {
            // If top level book page.
            $build['link_add_child_book_page'] = $add_book_page_urls['add_child_book_page']->toString();
            $build['link_add_child_book_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new book page'), $add_book_page_urls['add_child_book_page'])->toRenderable();
          }
          else {
            // If child book page.
            $build['link_add_current_level_book_page'] = $add_book_page_urls['add_current_level_book_page']->toString();
            $build['link_add_current_level_book_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new page on the current level'), $add_book_page_urls['add_current_level_book_page'])->toRenderable();
            $build['link_add_current_level_book_page_renderable']['#suffix'] = '<br>';
            $build['link_add_child_book_page'] = $add_book_page_urls['add_child_book_page']->toString();
            $build['link_add_child_book_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new page below this page'), $add_book_page_urls['add_child_book_page'])->toRenderable();
          }
        }
        break;

    }
  }

  /**
   * Gets book page add form Urls from the current book page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity node book.
   *
   * @return \Drupal\Core\Url[]
   *   The Url object for the book page add form route.
   */
  private function getBookPageAddFormUrls(EntityInterface $entity) {
    $route_parameters = [
      'node_type' => 'book',
    ];
    $link_options = [
      'add_current_level_book_page' => [
        'query' => [
          'parent' => $entity->book['pid'],
        ],
      ],
      'add_child_book_page' => [
        'query' => [
          'parent' => $entity->id(),
        ],
      ],
    ];
    $links = [];
    foreach ($link_options as $key => $options) {
      $links[$key] = Url::fromRoute('node.add', $route_parameters, $options);
    }
    return $links;
  }

}
