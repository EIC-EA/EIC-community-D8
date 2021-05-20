<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\oec_group_features\GroupFeaturePluginBase;

/**
 * Group feature plugin implementation for Discussions.
 *
 * @GroupFeature(
 *   id = "eic_groups_discussions",
 *   label = @Translation("Discussions"),
 *   description = @Translation("Group discussions features.")
 * )
 */
class GroupDiscussions extends GroupFeaturePluginBase {

  use StringTranslationTrait;

  /**
   * Route of the discussions overview.
   *
   * @var string
   */
  const OVERVIEW_DISCUSSIONS_ROUTE = 'view.group_overviews.page_1';

  /**
   * Permissions to grant or revoke.
   *
   * @var array
   */
  const DISCUSSIONS_PERMS = [
    'access discussions overview',
    'create group_node:discussion entity',
  ];

  /**
   * {@inheritdoc}
   */
  public function enable(GroupInterface $group) {
    if ($url = Url::fromRoute(self::OVERVIEW_DISCUSSIONS_ROUTE)) {
      // Check if we have a main menu for this group.
      foreach ($group_menus = group_content_menu_get_menus_per_group($group) as $group_menu) {
        if ($group_menu->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_content_menu:group_main_menu') {
          // Enable the menu item.
          $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()->id();
          $this->enableMenuItem($this->getMenuItem($url, $menu_name));
        }
      }
    }

    // Enable the permissions.
    if ($group_permissions = $this->getGroupPermissions($group)) {
      $group_permissions = $this->addRolePermissionsToGroup($group_permissions, 'group-member', self::DISCUSSIONS_PERMS);
      // @todo Get additional roles from the (oec_)group_flex settings.
      $this->saveGroupPermissions($group_permissions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disable(GroupInterface $group) {
    if ($url = Url::fromRoute(self::OVERVIEW_DISCUSSIONS_ROUTE)) {
      // Check if we have a main menu for this group.
      foreach ($group_menus = group_content_menu_get_menus_per_group($group) as $group_menu) {
        if ($group_menu->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_content_menu:group_main_menu') {
          // Disable menu item.
          $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()->id();
          $this->disableMenuItem($this->getMenuItem($url, $menu_name));
        }
      }
    }

    // Disable the permissions.
    if ($group_permissions = $this->getGroupPermissions($group)) {
      $group_permissions = $this->removeRolePermissionsFromGroup($group_permissions, 'group-member', self::DISCUSSIONS_PERMS);
      // @todo Get additional roles from the (oec_)group_flex settings.
      $this->saveGroupPermissions($group_permissions);
    }
  }

  /**
   * Prepares a MenuLinkContent object.
   *
   * @param \Drupal\Core\Url $url
   *   The URL object to be used by the menu item.
   * @param string $menu_name
   *   The menu name this menu item should be assigned to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A MenuLinkContent object (unsaved).
   */
  protected function getMenuItem(Url $url, string $menu_name) {
    return $this->menuLinkContentStorage->create([
      'title' => $this->t('Discussions'),
      'link' => [
        'uri' => 'route:' . $url->getRouteName(),
      ],
      'menu_name' => $menu_name,
      'weight' => 2,
    ]);
  }

}
