<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\oec_group_features\GroupFeaturePluginBase;

/**
 * Group feature plugin implementation for Members.
 *
 * @GroupFeature(
 *   id = "eic_groups_members",
 *   label = @Translation("Members"),
 *   description = @Translation("Group members features.")
 * )
 */
class GroupMembers extends GroupFeaturePluginBase {

  use StringTranslationTrait;

  /**
   * Route of the members overview.
   *
   * @var string
   */
  const OVERVIEW_MEMBERS_ROUTE = 'view.group_overviews.members';

  /**
   * {@inheritdoc}
   */
  public function enable(GroupInterface $group) {
    // Menu.
    $url_params = ['group' => $group->id()];
    if ($url = Url::fromRoute(self::OVERVIEW_MEMBERS_ROUTE, $url_params)) {
      // Check if we have a main menu for this group.
      foreach ($group_menus = group_content_menu_get_menus_per_group($group) as $group_menu) {
        if ($group_menu->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_content_menu:group_main_menu') {
          // Enable the menu item.
          $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()->id();
          $this->enableMenuItem($this->getMenuItem($url, $menu_name));
        }
      }
    }

    // Permissions: enable the permissions.
    $config = $this->configFactory->get('eic_groups.group_features.' . $this->getPluginId());
    $group_permissions = $this->getGroupPermissions($group);
    foreach ($config->get('roles') as $role) {
      $group_permissions = $this->addRolePermissionsToGroup($group_permissions, $role, $config->get('permissions'));
    }
    $this->saveGroupPermissions($group_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function disable(GroupInterface $group) {
    // Menu.
    $url_params = ['group' => $group->id()];
    if ($url = Url::fromRoute(self::OVERVIEW_MEMBERS_ROUTE, $url_params)) {
      // Check if we have a main menu for this group.
      foreach ($group_menus = group_content_menu_get_menus_per_group($group) as $group_menu) {
        if ($group_menu->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_content_menu:group_main_menu') {
          // Disable menu item.
          $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()->id();
          $this->disableMenuItem($this->getMenuItem($url, $menu_name));
        }
      }
    }

    // Permissions: disable the permissions.
    $config = $this->configFactory->get('eic_groups.group_features.' . $this->getPluginId());
    $group_permissions = $this->getGroupPermissions($group);
    foreach ($config->get('roles') as $role) {
      $group_permissions = $this->removeRolePermissionsFromGroup($group_permissions, $role, $config->get('permissions'));
    }
    $this->saveGroupPermissions($group_permissions);
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
      'title' => $this->t('Members'),
      'link' => [
        'uri' => 'internal:/' . $url->getInternalPath(),
      ],
      'menu_name' => $menu_name,
      'weight' => 5,
    ]);
  }

}
