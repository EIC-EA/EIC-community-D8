<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for EIC Groups routes.
 */
class GroupWikiOverviewController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Builds the response.
   */
  public function build(GroupInterface $group) {
    // @todo Check if there are wiki pages in the group and if not, show a
    // message "No Wiki pages (yet)" and a button to add a new wiki page.
    $build['content'] = [];
    return $build;
  }

}
