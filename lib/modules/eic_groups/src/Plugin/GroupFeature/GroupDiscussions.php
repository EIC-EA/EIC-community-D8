<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

/**
 * Group feature plugin implementation for Discussions.
 *
 * @GroupFeature(
 *   id = "eic_groups_discussions",
 *   label = @Translation("Discussions"),
 *   description = @Translation("Group discussions features.")
 * )
 */
class GroupDiscussions extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the discussions overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'view.group_overviews.page_1';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 2);
    return $menu_item;
  }

}
