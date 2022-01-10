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
   * Anchor of current group route canonical.
   *
   * @var string
   */
  const ANCHOR_ID = 'announcements';

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
