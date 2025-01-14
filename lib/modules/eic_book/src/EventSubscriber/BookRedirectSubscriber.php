<?php

namespace Drupal\eic_book\EventSubscriber;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Eic book event subscriber.
 */
class BookRedirectSubscriber implements EventSubscriberInterface {

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
   * Constructs a new BookRedirectSubscriber instance.
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
   * Checks the first-level book page if is published, returns 403 otherwise.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
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

    if (!($node instanceof NodeInterface) || $node->getType() !== 'book') {
      return;
    }

    $parent_book_nid = $node->book['pid'];
    if (!empty($parent_book_nid)) {
      // we have a parent book. check if published
      $parent_book_node = $this->entityTypeManager->getStorage('node')->load($parent_book_nid);
      if (!$parent_book_node->isPublished()) {
        $cacheability = new CacheableMetadata();
        $cacheability->addCacheableDependency($parent_book_node);
        $cacheability->addCacheableDependency($node);
        throw new CacheableAccessDeniedHttpException($cacheability, 'Parent book is not published.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['redirectBookPage'],
    ];
  }

}
