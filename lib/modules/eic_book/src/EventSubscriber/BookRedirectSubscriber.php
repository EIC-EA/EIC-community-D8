<?php

namespace Drupal\eic_book\EventSubscriber;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_user\UserHelper;
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
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new BookRedirectSubscriber instance.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(BookManagerInterface $book_manager, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, AccountInterface $current_user) {
    $this->bookManager = $book_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->currentUser = $current_user;
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

    if (UserHelper::isPowerUser($this->currentUser)) {
      return;
    }

    $book_nids = [];
    $result = [];
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($node);

    $book = $node->book;
    $depth = 1;
    // We skip the current node.
    while (!empty($book['p' . ($depth + 1)])) {
      $book_nids[] = $book['p' . $depth];
      $depth++;
    }
    /** @var \Drupal\node\NodeInterface[] $parent_books */
    $parent_books = $this->entityTypeManager->getStorage('node')->loadMultiple($book_nids);
    $parent_books = array_map([$this->entityRepository, 'getTranslationFromContext'], $parent_books);
    if (count($parent_books) > 0) {
      $depth = 1;
      while (!empty($book['p' . ($depth + 1)])) {
        if (!empty($parent_books[$book['p' . $depth]]) && ($parent_book = $parent_books[$book['p' . $depth]])) {
          $result[$parent_book->id()] = $parent_book->isPublished();
          $cacheability->addCacheableDependency($parent_book);
        }
        $depth++;
      }
    }

    if (in_array(FALSE, array_values($result))) {
      throw new CacheableAccessDeniedHttpException($cacheability, 'Parent book is not published.');
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
