<?php

namespace Drupal\eic_groups\Breadcrumb;

use Drupal\book\BookBreadcrumbBuilder;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a breadcrumb builder for groups and nodes that belong to groups.
 */
class GroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The book breadcrumb builder service.
   *
   * @var \Drupal\book\BookBreadcrumbBuilder
   */
  protected $bookBreadcrumbBuilder;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs the GroupBreadcrumbBuilder.
   *
   * @param \Drupal\book\BookBreadcrumbBuilder $book_breadcrumb_builder
   *   The book breadcrumb builder service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(BookBreadcrumbBuilder $book_breadcrumb_builder, AccountInterface $account) {
    $this->bookBreadcrumbBuilder = $book_breadcrumb_builder;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies = FALSE;

    switch ($route_match->getRouteName()) {
      case 'entity.node.canonical':
        $node = $route_match->getParameter('node');

        if ($node instanceof NodeInterface) {
          $applies = GroupContent::loadByEntity($node) ? TRUE : FALSE;
        }
        break;

      case 'entity.group.canonical':
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
    // Adds link to navigate back to the list of groups.
    $links[] = Link::createFromRoute($this->t('Groups'), 'view.global_overviews.page_1');

    switch ($route_match->getRouteName()) {
      case 'entity.node.canonical':
        $node = $route_match->getParameter('node');

        if ($node instanceof NodeInterface) {
          // Adds the user access as cacheable dependency.
          if ($access = $node->access('view', $this->account, TRUE)) {
            $breadcrumb->addCacheableDependency($access);
          }

          if ($group_content = GroupContent::loadByEntity($node)) {
            // Because a node can only belong to 1 group, we get the first
            // group content entity from the array.
            $group_content_entity = reset($group_content);
            $group = $group_content_entity->getGroup();
            $links[] = $group->toLink();

            switch ($node->bundle()) {
              case 'book':
              case 'wiki_page':
                $book_breadcrumb = $this->bookBreadcrumbBuilder->build($route_match);
                // Replace links with book breadcrumb links.
                $links = $book_breadcrumb->getLinks();
                // Places the group link right after the "Home" link.
                array_splice($links, 1, 0, [$group->toLink()]);
                // Places the groups overview link right after the "Home" link.
                array_splice($links, 1, 0, [Link::createFromRoute($this->t('Groups'), 'view.global_overviews.page_1')]);
                // Replaces book link text with "Wiki".
                if ($node->bundle() === 'wiki_page') {
                  $links[3]->setText($this->t('Wiki'));
                }
                // We want to keep cache contexts and cache tags from book
                // breadcrumb.
                $breadcrumb->addCacheContexts($book_breadcrumb->getCacheContexts());
                $breadcrumb->addCacheTags($book_breadcrumb->getCacheTags());
                break;

            }

            // We add the node and group objects as cacheable dependency.
            $breadcrumb->addCacheableDependency($node);
            $breadcrumb->addCacheableDependency($group);
          }
        }
        break;

      case 'entity.group.canonical':
        $group = $route_match->getParameter('group');

        if ($group instanceof GroupInterface) {
          // Adds the user access as cacheable dependency.
          if ($access = $group->access('view', $this->account, TRUE)) {
            $breadcrumb->addCacheableDependency($access);
          }

          // We add the group as cacheable dependency.
          $breadcrumb->addCacheableDependency($group);
        }
        break;

    }

    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);
    return $breadcrumb;
  }

}
