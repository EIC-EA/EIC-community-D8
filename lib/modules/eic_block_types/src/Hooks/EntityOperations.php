<?php

namespace Drupal\eic_block_types\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks for block_content entities.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $nodeStatisticsDatabaseStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * The Flag count manager.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  private $flagCountManager;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_db_storage
   *   The Entity file download count service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count_manager
   *   The Flag count manager.
   */
  public function __construct(NodeStatisticsDatabaseStorage $node_statistics_db_storage, EntityFieldManagerInterface $entity_field_manager, FlagCountManagerInterface $flag_count_manager) {
    $this->nodeStatisticsDatabaseStorage = $node_statistics_db_storage;
    $this->entityFieldManager = $entity_field_manager;
    $this->flagCountManager = $flag_count_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('statistics.storage.node'),
      $container->get('entity_field.manager'),
      $container->get('flag.count')
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
  public function blockContentView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    switch ($entity->bundle()) {
      case 'latest_news_stories':
        $this->viewLatestNewsStoriesStatistics($build, $entity, $display, $view_mode);
        break;

    }
  }

  /**
   * Adds nodes statistics to the latest news and stories block.
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
  private function viewLatestNewsStoriesStatistics(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $nodes = [];

    // Adds News/Stories to the array of nodes to process.
    if (!$entity->get('field_articles')->isEmpty()) {
      $nodes = $entity->get('field_articles')->referencedEntities();
    }

    // Adds featured News/Story to the beginning of array of nodes.
    if (!$entity->get('field_featured_article')->isEmpty()) {
      array_unshift($nodes, $entity->get('field_featured_article')->entity);
    }

    $build['node_stats'] = [];

    // Process the array of nodes and add statistics to the renderable array.
    foreach ($nodes as $node) {
      if (!$node instanceof NodeInterface) {
        continue;
      }

      // Page views statistic.
      $page_views = 0;
      if ($node_views = $this->nodeStatisticsDatabaseStorage->fetchView($node->id())) {
        $page_views = $node_views->getTotalCount();
      }
      $build['node_stats'][$node->id()]['page_views'] = [
        '#markup' => '',
        '#value' => $page_views,
      ];

      // Comments count statistic.
      $comment_count = 0;
      $entity_fields = $this->entityFieldManager->getFieldDefinitions('node', $node->bundle());
      foreach ($entity_fields as $field) {
        switch ($field->getType()) {
          case 'comment':
            $comment_count += (int) $node->get($field->getName())->comment_count;
            break;

        }
      }
      $build['node_stats'][$node->id()]['comments_count'] = [
        '#markup' => '',
        '#value' => $comment_count,
      ];

      // Flags count statistic.
      $build['node_stats'][$node->id()]['flag_counts'] = [
        '#markup' => '',
        '#items' => $this->flagCountManager->getEntityFlagCounts($node),
      ];
    }
  }

}
