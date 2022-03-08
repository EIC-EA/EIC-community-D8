<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eic_groups\EICGroupsHelperInterface;
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
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(RouteMatchInterface $route_match, EICGroupsHelperInterface $eic_groups_helper) {
    $this->routeMatch = $route_match;
    $this->eicGroupsHelper = $eic_groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Implements hook_preprocess_links__node().
   */
  public function preprocessLinksNode(&$variables) {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        // Removes "Add child page" from book and wiki pages that belong to a
        // group.
        if (($node = $this->routeMatch->getParameter('node')) && $node instanceof NodeInterface) {
          if (in_array($node->bundle(), ['book', 'wiki_page'])) {
            if ($this->eicGroupsHelper->getOwnerGroupByEntity($node)) {
              unset($variables['links']['book_add_child']);
            }
          }
        }
        break;

    }
  }

}
