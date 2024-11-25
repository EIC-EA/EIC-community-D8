<?php

namespace Drupal\eic_groups\Plugin\GroupFeature\GroupAnchorFeature;

use Drupal\Core\Url;
use Drupal\eic_groups\Plugin\GroupFeature\EicGroupsGroupFeaturePluginBase;

/**
 * Group feature plugin implementation for Projects anchor.
 *
 * @GroupFeature(
 *   id = "eic_groups_anchor_projects",
 *   label = @Translation("Projects"),
 *   description = @Translation("Group projects anchor features.")
 * )
 */
class GroupAnchorProjects extends EicGroupsGroupFeaturePluginBase {

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'projects';

  /**
   * {@inheritdoc}
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $menu_item */
    $menu_item = parent::getMenuItem($url, $menu_name);
    // Set a specific weight for the menu item.
    $menu_item->set('weight', 15);

    return $menu_item;
  }

}
