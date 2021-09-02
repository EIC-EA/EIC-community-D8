<?php

namespace Drupal\eic_comments\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that provides implementations of entity hooks.
 *
 * @package Drupal\eic_comments\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity field manager.
   */
  public function __construct(
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * Provides flags count for the given entity.
   *
   * @param array $build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $comment_count = 0;
    $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($entity_fields as $field) {
      switch ($field->getType()) {
        case 'comment':
          $comment_count += (int) $entity->get($field->getName())->comment_count;
          break;

      }
    }
    $build['comments_count'] = [
      '#markup' => '',
      '#value' => $comment_count,
    ];
  }

}
