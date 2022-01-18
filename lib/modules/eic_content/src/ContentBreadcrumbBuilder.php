<?php

namespace Drupal\eic_content;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\node\NodeInterface;

/**
 * Provides a breadcrumb builder for nodes.
 */
class ContentBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ContentBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies = FALSE;

    switch ($route_match->getRouteName()) {
      case 'entity.node.canonical':
        $applies = TRUE;
        break;

    }

    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    // Adds homepage link.
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');

    $node = $route_match->getParameter('node');

    if ($node instanceof NodeInterface) {
      // Adds the user access as cacheable dependency.
      if ($access = $node->access('view', $this->currentUser->getAccount(), TRUE)) {
        $breadcrumb->addCacheableDependency($access);
      }

      switch ($node->bundle()) {
        case 'news':
        case 'story':
          $links[] = GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::NEWS_STORIES);
          break;

      }

      // We add the node objects as cacheable dependency.
      $breadcrumb->addCacheableDependency($node);
    }

    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);
    return $breadcrumb;
  }

}
