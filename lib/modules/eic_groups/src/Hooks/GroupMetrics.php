<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides metrics around group entities.
 */
class GroupMetrics implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The EIC GRoups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The entity type manager.
   */
  public function __construct(
    EICGroupsHelper $eic_groups_helper
  ) {
    $this->groupsHelper = $eic_groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Implements hook_eic_groups_metrics_info().
   */
  public function groupMetricsInfo():array {
    return [
      'eic_groups_group_members' => [
        'label' => $this->t('Group members'),
        'value_callback' => get_class($this) . '::groupMetricsValue',
      ],
      'eic_groups_test' => [
        'label' => $this->t('Test'),
        'value_callback' => get_class($this) . '::test',
      ],
    ];
  }

  /**
   * Returns the metric value for the given group.
   *
   * @param string $metric_id
   *   The ID of the metric.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which we get the metric.
   * @param array $configuration
   *   The views plugin configuration.
   *
   * @return int|null
   *   The value for the metric or NULL if not applicable.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function groupMetricsValue(string $metric_id, GroupInterface $group, array $configuration = []) {
    switch ($metric_id) {
      case 'eic_groups_group_members':
        return count($group->getMembers($configuration['roles']));

    }

    return NULL;
  }

}
