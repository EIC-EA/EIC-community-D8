<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

/**
 * Group feature plugin implementation for Latest activity stream.
 *
 * @GroupFeature(
 *   id = "eic_groups_latest_activity_stream",
 *   label = @Translation("Latest activity"),
 *   description = @Translation("Group latest activity stream feature.")
 * )
 */
class GroupLatestActivityStream extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the latest activity stream overview.
   *
   * @todo Change the route name once we have the final overview page.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'eic_overviews.groups.overview_page.latest_activity_stream';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $menu_item */
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 1);
    return $menu_item;
  }

}
