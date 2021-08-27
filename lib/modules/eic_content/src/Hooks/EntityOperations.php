<?php

namespace Drupal\eic_content\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $nodeStatisticsDatabaseStorage;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_db_storage
   *   The Entity file download count service.
   */
  public function __construct(NodeStatisticsDatabaseStorage $node_statistics_db_storage) {
    $this->nodeStatisticsDatabaseStorage = $node_statistics_db_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('statistics.storage.node')
    );
  }

  /**
   * Acts on hook_node_view() for node entities.
   *
   * @param array $build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $page_views = 0;
    if ($node_views = $this->nodeStatisticsDatabaseStorage->fetchView($entity->id())) {
      $page_views = $node_views->getTotalCount();
    }
    $build['page_views'] = [
      '#markup' => '',
      '#value' => $page_views,
    ];
  }

}
