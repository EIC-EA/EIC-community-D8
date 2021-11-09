<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route response for the About page.
 */
class AboutPageController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AboutPageController object.
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
   * Builds the page title.
   */
  public function title(GroupInterface $group) {
    return $this->t('@group_name - About', ['@group_name' => $group->label()]);
  }

  /**
   * Returns the About page for a given group.
   *
   * @return array
   *   A simple renderable array.
   */
  public function build(GroupInterface $group) {
    $view_builder = $this->entityTypeManager->getViewBuilder('group');
    return $view_builder->view($group, 'about_page');
  }

}
