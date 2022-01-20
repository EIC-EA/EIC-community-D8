<?php

namespace Drupal\eic_group_statistics\Plugin\views\field;

use Drupal\group\Entity\GroupContentInterface;
use Drupal\views\Plugin\views\field\Date;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field for group member latest activity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_member_latest_activity")
 */
class GroupMemberLatestActivity extends Date {

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
    $entity = $values->_entity;
    $membership = NULL;

    if ($entity instanceof GroupContentInterface) {
      /** @var \Drupal\group\Entity\GroupContentInterface $entity */
      if ($entity->getGroupContentType()->getContentPluginId() == 'group_membership') {
        $membership = $entity;
      }
    }
    else {
      // Depending on the base table of the view, the entity can be something
      // else than a GroupContent entity, so we need to check the relationships.
      /** @var \Drupal\group\Entity\GroupContentInterface $relationship_entity */
      foreach ($values->_relationship_entities as $relationship_entity) {
        if (!$relationship_entity instanceof GroupContentInterface) {
          continue;
        }

        // We get the first encountered membership.
        if ($relationship_entity->getGroupContentType()->getContentPluginId() == 'group_membership') {
          $membership = $relationship_entity;
          break;
        }
      }

    }

    // If we don't have a proper membership, just return null.
    if (!$membership) {
      return NULL;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface $membership */
    return $this->groupsStatisticsHelper->getGroupMemberLatestActivity($membership->getGroup(), $membership->getEntity());
  }

}
