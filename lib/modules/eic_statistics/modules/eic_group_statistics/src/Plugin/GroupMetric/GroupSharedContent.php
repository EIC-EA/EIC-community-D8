<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group metric plugin implementation for group shared content.
 *
 * @GroupMetric(
 *   id = "eic_groups_shared_content",
 *   label = @Translation("Group shared content"),
 *   description = @Translation("Provides a counter for group shared content.")
 * )
 */
class GroupSharedContent extends GroupMetricPluginBase {

  /**
   * The EIC share manager.
   *
   * @var \Drupal\eic_share_content\Service\ShareManager
   */
  protected $shareManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->shareManager = $container->get('eic_share_content.share_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []): int {
    $count = 0;
    // Get group nodes first.
    foreach ($this->groupsHelper->getGroupNodes($group) as $node) {
      $count += count($this->shareManager->getSharedEntities($node));
    }
    return $count;
  }

}
