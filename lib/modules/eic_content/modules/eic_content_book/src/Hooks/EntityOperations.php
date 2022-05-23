<?php

namespace Drupal\eic_content_book\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content\EICContentHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The EIC content helper.
   *
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  protected $eicContentHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\eic_content\EICContentHelperInterface $eic_content_helper
   *   The EIC content helper.
   */
  public function __construct(RouteMatchInterface $route_match, AccountProxyInterface $current_user, EICContentHelperInterface $eic_content_helper) {
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->eicContentHelper = $eic_content_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('eic_content.helper')
    );
  }

  /**
   * Implements hook_node_view().
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        if ($entity->bundle() === 'book') {
          // Ignore book page that belongs to a group.
          if ($this->eicContentHelper->getGroupContentByEntity($entity)) {
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

          if (isset($entity->book) && empty($entity->book['pid'])) {
            // If top level book page.
            $build['link_add_child_book_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new book page'), $add_book_page_urls['add_child_book_page'])->toRenderable();
          }
          elseif (isset($entity->book) && !empty($entity->book['pid'])) {
            // If child book page.
            $build['link_add_current_level_book_page_renderable'] = Link::fromTextAndUrl($this->t('Add page on same level'), $add_book_page_urls['add_current_level_book_page'])->toRenderable();
            $build['link_add_current_level_book_page_renderable']['#suffix'] = '<br>';
            $build['link_add_child_book_page_renderable'] = Link::fromTextAndUrl($this->t('Add a child page'), $add_book_page_urls['add_child_book_page'])->toRenderable();
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
      'add_child_book_page' => [
        'query' => [
          'parent' => $entity->id(),
        ],
      ],
    ];
    if (!empty($entity->book['pid'])) {
      $link_options['add_current_level_book_page'] = [
        'query' => [
          'parent' => $entity->book['pid'],
        ],
      ];
    }
    $links = [];
    foreach ($link_options as $key => $options) {
      $links[$key] = Url::fromRoute('node.add', $route_parameters, $options);
    }
    return $links;
  }

}
