<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;

/**
 * Group feature plugin implementation for Files.
 *
 * @GroupFeature(
 *   id = "eic_groups_files",
 *   label = @Translation("Files"),
 *   description = @Translation("Group files features.")
 * )
 */
class GroupFiles extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the files overview.
   *
   * @todo Change the route name once we have the final files overview page.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = '<front>';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 7);
    return $menu_item;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPrimaryOverviewRoute(GroupInterface $group) {
    return Url::fromRoute(static::PRIMARY_OVERVIEW_ROUTE);
  }

}
