<?php

namespace Drupal\eic_topics;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_topics\Constants\Topics;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class TermBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the TermBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'entity.taxonomy_term.canonical'
      && $route_match->getParameter('taxonomy_term') instanceof TermInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $term = $route_match->getParameter('taxonomy_term');
    // Breadcrumb needs to have terms cacheable metadata as a cacheable
    // dependency even though it is not shown in the breadcrumb because e.g. its
    // parent might have changed.
    $breadcrumb->addCacheableDependency($term);
    $parents = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadAllParents($term->id());
    // Remove current term being accessed.
    array_shift($parents);
    $vid = $term->get('vid')->getValue();
    $vid = reset($vid)['target_id'];

    foreach (array_reverse($parents) as $term) {
      $term = $this->entityRepository->getTranslationFromContext($term);
      $child_parents = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadParents($term->id());

      // We don't show the first level term of topics taxonomies.
      if (
        empty($child_parents) &&
        Topics::TERM_VOCABULARY_TOPICS_ID === $vid
      ) {
        continue;
      }

      $breadcrumb->addCacheableDependency($term);
      $breadcrumb->addLink(
        Link::createFromRoute(
          $term->getName(),
          'entity.taxonomy_term.canonical', [
            'taxonomy_term' => $term->id(),
          ]
        )
      );
    }

    $tid = $term->id();
    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route'])->addCacheTags(["taxonomy_term:$tid"]);

    return $breadcrumb;
  }

}
