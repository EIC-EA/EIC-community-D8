<?php

namespace Drupal\eic_topics\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_topics\Constants\Topics;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides an Event Subscriber for kernel events.
 */
class TopicsRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TopicsRedirectSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [
        ['redirectTopLevelTopics'],
      ],
    ];
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function redirectTopLevelTopics(RequestEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->get('_route') !== 'entity.taxonomy_term.canonical') {
      return;
    }

    $term = $request->attributes->get('taxonomy_term');

    if (!($term instanceof TermInterface)) {
      return;
    }

    $vid = $term->get('vid')->getValue();
    $vid = reset($vid)['target_id'];

    $parents = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadParents($term->id());

    // We don't show the first level term of topics taxonomies.
    if (empty($parents) && Topics::TERM_VOCABULARY_TOPICS_ID === $vid) {
      throw new NotFoundHttpException();
    }
  }

}
