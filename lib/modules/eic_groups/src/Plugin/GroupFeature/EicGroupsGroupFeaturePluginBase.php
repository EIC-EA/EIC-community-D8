<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\oec_group_features\GroupFeaturePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group feature plugin implementation for Discussions.
 */
class EicGroupsGroupFeaturePluginBase extends GroupFeaturePluginBase {

  use StringTranslationTrait;

  /**
   * Route of the primary overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = '';

  /**
   * The EIC Group Helper class.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $eicGroupHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->eicGroupHelper = $container->get('eic_groups.helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(GroupInterface $group) {
    // Menu: enable the menu item.
    if ($url = $this->getPrimaryOverviewRoute($group)) {
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
    if ($group_permissions = $this->getGroupPermissions($group)) {
      $config = $this->configFactory->get('eic_groups.group_features.' . $this->getPluginId());
      foreach ($config->get('roles') as $role) {
        $group_permissions = $this->addRolePermissionsToGroup($group_permissions, $role, $config->get('permissions'));
      }
      $this->saveGroupPermissions($group_permissions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function disable(GroupInterface $group) {
    // Menu: disable the menu item.
    if ($url = $this->getPrimaryOverviewRoute($group)) {
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
    if ($group_permissions = $this->getGroupPermissions($group)) {
      $config = $this->configFactory->get('eic_groups.group_features.' . $this->getPluginId());
      foreach ($config->get('roles') as $role) {
        $group_permissions = $this->removeRolePermissionsFromGroup($group_permissions, $role, $config->get('permissions'));
      }
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
      'title' => $this->label(),
      'link' => [
        'uri' => 'internal:/' . $url->getInternalPath(),
      ],
      'menu_name' => $menu_name,
      'weight' => 2,
    ]);
  }

  /**
   * Returns an Url object for the PRIMARY_OVERVIEW_ROUTE.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  protected function getPrimaryOverviewRoute(GroupInterface $group) {
    // We assume here that the overview route takes the group ID as unique
    // argument.
    $url_params = ['group' => $group->id()];
    return Url::fromRoute(static::PRIMARY_OVERVIEW_ROUTE, $url_params);
  }

}
