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
      $this->handleMenuItem($group, $url, 'enable');
    }

    // Permissions: enable the permissions.
    $this->handlePermissions($group, 'enable');
  }

  /**
   * {@inheritdoc}
   */
  public function disable(GroupInterface $group) {
    // Menu: disable the menu item.
    if ($url = $this->getPrimaryOverviewRoute($group)) {
      $this->handleMenuItem($group, $url, 'disable');
    }

    // Permissions: disable the permissions.
    $this->handlePermissions($group, 'disable');
  }

  /**
   * Enables or disable a menu item for a specific group menu.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param $url
   *   The URL object for which we handle a menu item.
   * @param string $op
   *   The operation to perform. It can be one of the following:
   *   - enable
   *   - disable
   * @param string $menu_name
   *   The group menu name for which we perform the operation.
   *
   * @return bool
   *   TRUE if the action was performed successfully, FALSE otherwise.
   */
  protected function handleMenuItem(GroupInterface $group, $url, $op = 'enable', $menu_name = 'group_main_menu') {
    // Check if we have the target menu for this group.
    foreach (group_content_menu_get_menus_per_group($group) as $group_menu) {
      if ($group_menu->getGroupContentType()->getContentPlugin()->getPluginId() == "group_content_menu:$menu_name") {
        // Disable menu item.
        $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()->id();
        switch ($op) {
          case 'enable':
            $this->enableMenuItem($this->getMenuItem($url, $menu_name));
            return TRUE;
            break;
          case 'disable':
            $this->disableMenuItem($this->getMenuItem($url, $menu_name));
            return FALSE;
            break;
        }
      }
    }
    return FALSE;
  }

  /**
   * Enables or disables group permissions for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $op
   *   The operation to perform. It can be one of the following:
   *   - enable
   *   - disable
   *
   * @return bool
   *   TRUE if the action was performed successfully, FALSE otherwise.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function handlePermissions(GroupInterface $group, $op = 'enable') {
    if ($group_permissions = $this->getGroupPermissions($group)) {
      $config = $this->configFactory->get('eic_groups.group_features.' . $this->getPluginId());
      foreach ($config->get('roles') as $role) {
        switch ($op) {
          case 'enable':
            $group_permissions = $this->addRolePermissionsToGroup($group_permissions, $role, $config->get('permissions'));
            break;
          case 'disable':
            $group_permissions = $this->removeRolePermissionsFromGroup($group_permissions, $role, $config->get('permissions'));
            break;
        }

      }
      $this->saveGroupPermissions($group_permissions);
      return TRUE;
    }
    return FALSE;
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
