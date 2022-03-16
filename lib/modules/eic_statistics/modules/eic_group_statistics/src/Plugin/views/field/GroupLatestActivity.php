<?php

namespace Drupal\eic_group_statistics\Plugin\views\field;

use Drupal\group\Entity\GroupInterface;
use Drupal\views\Plugin\views\field\Date;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field for group latest activity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_latest_activity")
 */
class GroupLatestActivity extends Date {

  /**
   * The EIC Groups statistics helper service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelper
   */
  protected $groupsStatisticsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->groupsStatisticsHelper = $container->get('eic_group_statistics.helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $group = $values->_entity;
    if (!$group instanceof GroupInterface) {
      return NULL;
    }

    return $this->groupsStatisticsHelper->getGroupLatestActivity($group);
  }

}
