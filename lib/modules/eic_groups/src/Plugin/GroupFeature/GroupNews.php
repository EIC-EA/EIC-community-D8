<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

/**
 * Group feature plugin implementation for News.
 *
 * @GroupFeature(
 *   id = "eic_groups_news",
 *   label = @Translation("News"),
 *   description = @Translation("Group news features.")
 * )
 */
class GroupNews extends EicGroupsGroupFeaturePluginBase {

  /**
   * Route of the news overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'eic_overviews.groups.overview_page.news';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $menu_item */
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 4);
    return $menu_item;
  }

}
