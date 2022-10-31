<?php

namespace Drupal\eic_content_book\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Preprocess.
 *
 * Implementations of preprocess hooks.
 */
class Preprocess implements ContainerInjectionInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * @param \Drupal\eic_content\EICContentHelperInterface $eic_content_helper
   *   The EIC Groups helper service.
   */
  public function __construct(RouteMatchInterface $route_match, EICContentHelperInterface $eic_content_helper) {
    $this->routeMatch = $route_match;
    $this->eicContentHelper = $eic_content_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper')
    );
  }

  /**
   * Implements hook_preprocess_links__node().
   */
  public function preprocessLinksNode(&$variables) {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        // Remove add child link from book pages that do not belong to a group.
        if (($node = $this->routeMatch->getParameter('node'))
          && $node instanceof NodeInterface
          && $node->bundle() === 'book'
          && !$this->eicContentHelper->getGroupContentByEntity($node, [], ["group_node:{$node->bundle()}"])
        ) {
          unset($variables['links']['book_add_child']);
        }
        break;

    }
  }

}
