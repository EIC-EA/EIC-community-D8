<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

/**
 * Group feature plugin implementation for Members.
 *
 * @GroupFeature(
 *   id = "eic_groups_members",
 *   label = @Translation("Members"),
 *   description = @Translation("Group members features.")
 * )
 */
class GroupMembers extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the members overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'view.group_overviews.members';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 5);
    return $menu_item;
  }

}
