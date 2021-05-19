<?php

namespace Drupal\eic_groups\EventSubscriber;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides an Event Subscriber for kernel events.
 */
class BookPageRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The group entity from the group purl context.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a new BookPageRedirectSubscriber instance.
   *
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   */
  public function __construct(EICGroupsHelperInterface $eic_groups_helper, BookManagerInterface $book_manager) {
    $this->group = $eic_groups_helper->getGroupFromRoute();
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [
        ['redirectBookPage'],
      ],
    ];
  }

  /**
   * Redirect requests from book node detail pages to the 1st level wiki page.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event object.
   */
  public function redirectBookPage(RequestEvent $event) {
    $request = $event->getRequest();

    // This is necessary because this also gets called on
    // node sub-tabs such as "edit", "revisions", etc.  This
    // prevents those pages from redirected.
    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }

    $node = $request->attributes->get('node');

    if (!($node instanceof NodeInterface)) {
      return;
    }

    // Only redirect book pages in the context of group.
    if ($node->getType() !== 'book' || is_null($this->group)) {
      return;
    }

    $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book, 2);
    $book_data = reset($data);
    if (!empty($book_data['below'])) {
      $wiki_page_nid = reset($book_data['below'])['link']['nid'];
      $wiki_page = Node::load($wiki_page_nid);
      $redirect_response = new CacheableRedirectResponse($wiki_page->toUrl()->toString());
      $redirect_response->addCacheableDependency($wiki_page);
      $redirect_response->send();
    }
  }

}
