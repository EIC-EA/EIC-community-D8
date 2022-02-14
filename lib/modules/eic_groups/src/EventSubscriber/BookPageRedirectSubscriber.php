<?php

namespace Drupal\eic_groups\EventSubscriber;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides an Event Subscriber for kernel events.
 */
class BookPageRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BookPageRedirectSubscriber instance.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(BookManagerInterface $book_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->bookManager = $book_manager;
    $this->entityTypeManager = $entity_type_manager;
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

    // If node is not a book we skip redirection.
    if ($node->getType() !== 'book') {
      return;
    }

    // If book node is not a group content we skip redirection.
    if (!$this->entityTypeManager->getStorage('group_content')->loadByEntity($node)) {
      return;
    }

    $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book, 2);
    $book_data = reset($data);
    if (!empty($book_data['below'])) {
      $wiki_page_nid = reset($book_data['below'])['link']['nid'];
      $wiki_page = $this->entityTypeManager->getStorage('node')->load($wiki_page_nid);
      $redirect_response = new CacheableRedirectResponse($wiki_page->toUrl()->toString(), 301);
      $redirect_response->addCacheableDependency($wiki_page);
      $redirect_response->addCacheableDependency($node);
      $event->setResponse($redirect_response);
    }
  }

}
