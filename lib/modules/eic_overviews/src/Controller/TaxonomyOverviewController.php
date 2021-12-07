<?php

namespace Drupal\eic_overviews\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route response for the topics overview page.
 */
class TaxonomyOverviewController extends ControllerBase {

  /**
   * The ID of the view to be used.
   *
   * @var string
   */
  const TAXONOMY_VIEWS_ID = 'taxonomy';

  /**
   * The ID of the views display to be used.
   *
   * @var string
   */
  const TAXONOMY_VIEWS_DISPLAY_ID = 'block_taxonomy_overview';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SystemController.
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the content for the Taxonomy overview page.
   *
   * @param string $vocabulary_id
   *   The vocabulary ID.
   *
   * @return array
   *   The renderable array.
   */
  public function buildPage(string $vocabulary_id) {
    // Check if we have an existing vocabulary.
    if (!$vocabulary = $this->entityTypeManager->getStorage("taxonomy_vocabulary")->load($vocabulary_id)) {
      throw new NotFoundHttpException();
    }

    $build = [];

    /** @var \Drupal\taxonomy\VocabularyInterface $top_level_terms */
    $top_level_terms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($vocabulary->id(), 0, 1, TRUE);

    // Get one views block for each top level parent.
    foreach ($top_level_terms as $top_level_term) {
      if ($view = Views::getView(self::TAXONOMY_VIEWS_ID)) {
        $args = [$top_level_term->id(), $vocabulary_id];
        $build['tid-' . $top_level_term->id()] = $view->preview(self::TAXONOMY_VIEWS_DISPLAY_ID, $args);
      }
    }

    return $build;
  }

}
