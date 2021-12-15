<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

/**
 * Group feature plugin implementation for Discussions.
 *
 * @GroupFeature(
 *   id = "eic_groups_group_events",
 *   label = @Translation("Events"),
 *   description = @Translation("Group events features.")
 * )
 */
class GroupGroupEvents extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the events overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'eic_overviews.groups.overview_page.events';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $menu_item */
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 5);
    return $menu_item;
  }

}
