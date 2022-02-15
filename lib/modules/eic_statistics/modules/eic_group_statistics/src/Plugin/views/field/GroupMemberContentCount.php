<?php

namespace Drupal\eic_group_statistics\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field for group member content count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_member_content_count")
 */
class GroupMemberContentCount extends NumericField {

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
    if (!$membership = GroupMemberHelper::getMembership($values, $field)) {
      return NULL;
    }

    $conditions = [
      'entity_id.entity:node.uid' => $membership->getEntity()->id(),
    ];
    /** @var \Drupal\group\Entity\GroupContentInterface $membership */
    return $this->groupsStatisticsHelper->getGroupContentCount($membership->getGroup(), $conditions);
  }

}
