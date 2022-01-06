<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

/**
 * Group feature plugin implementation for Announcements.
 *
 * @GroupFeature(
 *   id = "eic_groups_announcements",
 *   label = @Translation("Announcements"),
 *   description = @Translation("Group announcements features.")
 * )
 */
class GroupAnnouncements extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the announcements overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'eic_overviews.groups.overview_page.news';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    $url = Url::fromUserInput('#announcements');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $menu_item */
    $menu_item = parent::getMenuItem($url, 'Announcements');
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 0);

    return $menu_item;
  }

}
