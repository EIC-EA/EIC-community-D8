<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\Url;

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
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'eic_overviews.groups.overview_page.files';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $menu_item */
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 3);
    return $menu_item;
  }

}
