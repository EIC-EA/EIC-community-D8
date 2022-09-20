<?php

namespace Drupal\eic_topics\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eic_topics\TopicsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an SearchOverviewBlock block.
 *
 * @Block(
 *   id = "eic_topics_statistics_block",
 *   admin_label = @Translation("EIC Topics Statistics"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class TopicStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var TopicsManager $topicsManager */
  private $topicsManager;

  /** @var RouteMatchInterface $routeMatch */
  private $routeMatch;

  /**
   * TopicStatisticsBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\eic_topics\TopicsManager $topics_manager
   * @param RouteMatchInterface $route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TopicsManager $topics_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->topicsManager = $topics_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_topics.topics_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * @return array
   */
  public function build() {
    /** @var \Drupal\taxonomy\TermInterface $term */
    if (!$term = $this->routeMatch->getParameter('taxonomy_term')) {
      return [];
    }

    return [
      '#theme' => 'topics_statistics_block',
      '#stats' => $this->topicsManager->generateTopicsStats($term->id()),
      '#cache' => [
        'tags' => [
          'taxonomy_term:' . $term->id(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Adds extra cache contexts to the block.
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

}
