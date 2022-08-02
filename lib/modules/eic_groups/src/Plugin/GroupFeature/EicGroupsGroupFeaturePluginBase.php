<?php

namespace Drupal\eic_groups\Plugin\GroupFeature;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\oec_group_features\GroupFeatureInterface;
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
   * Define an anchor.
   *
   * @var string
   */
  const ANCHOR_ID = '';

  /**
   * The EIC Group Helper class.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $eicGroupHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->eicGroupHelper = $container->get('eic_groups.helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(GroupInterface $group) {
    // Menu: enable the menu item.
    if ($url = $this->generateFeatureUrl($group)) {
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
    if ($url = $this->generateFeatureUrl($group)) {
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
   * @param \Drupal\Core\Url $url
   *   The URL object for which we handle a menu item.
   * @param string $op
   *   The operation to perform. It can be one of the following:
   *   - enable: enables the menu item.
   *   - disable: disables the menu item.
   * @param string $menu_name
   *   The group menu name for which we perform the operation.
   *
   * @return bool
   *   TRUE if the action was performed successfully, FALSE otherwise.
   */
  protected function handleMenuItem(
    GroupInterface $group,
    Url $url,
    $op = 'enable',
    $menu_name = 'group_main_menu'
  ) {
    // Check if we have the target menu for this group.
    foreach (group_content_menu_get_menus_per_group($group) as $group_menu) {
      if (
        $group_menu->getGroupContentType()->getContentPlugin()->getPluginId() ==
        "group_content_menu:$menu_name"
      ) {
        // Disable menu item.
        $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu
          ->getEntity()
          ->id();
        switch ($op) {
          case 'enable':
            $this->enableMenuItem($this->getMenuItem($url, $menu_name));
            return TRUE;

          case 'disable':
            $this->disableMenuItem($this->getMenuItem($url, $menu_name));
            return FALSE;

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
   *   - enable: enables the group permissions.
   *   - disable: disables the group permissions.
   *
   * @return bool
   *   TRUE if the action was performed successfully, FALSE otherwise.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function handlePermissions(GroupInterface $group, $op = 'enable') {
    if (
      ($group_permissions = $this->getGroupPermissions($group)) &&
      $group_permissions instanceof GroupPermissionInterface
    ) {
      $config = $this->configFactory->get(
        'eic_groups.group_features.' . $this->getPluginId()
      );

      // Filter out roles that don't belong to the group type.
      $group_type_roles = [];
      foreach ($config->get('roles') as $role) {
        if (strpos($role, $group->getGroupType()->id() . '-') !== 0) {
          continue;
        }
        $group_type_roles[] = $role;
      }

      // Initialize array of public role IDs to update group permissions.
      $public_role_ids = [];

      // Adds array of public roles to enable/disable group permissions for the
      // current group menu link. We check if every public role has permission
      // to view the group, otherwise the role is not allowed to view the menu
      // link.
      foreach ($group->getGroupType()->getRoles(TRUE) as $role) {
        if (in_array(
            $role->id(),
            $this->getGroupPublicRoleIds($group)
          ) && $role->hasPermission('view group')) {
          $public_role_ids[] = $role->id();
        }
      }

      switch ($op) {
        case 'enable':
          // Adds group permission for the private roles.
          foreach ($group_type_roles as $role) {
            $group_permissions = $this->addRolePermissionsToGroup(
              $group_permissions,
              $role,
              $config->get('permissions')
            );
          }

          // Adds group permission for the public roles.
          foreach ($public_role_ids as $role) {
            $group_permissions = $this->addRolePermissionsToGroup(
              $group_permissions,
              $role,
              $config->get('public_permissions')
            );
          }
          break;

        case 'disable':
          // Removes permission from the private roles.
          foreach ($group_type_roles as $role) {
            $group_permissions = $this->removeRolePermissionsFromGroup(
              $group_permissions,
              $role,
              $config->get('permissions')
            );
          }

          // Removes group permission from the public roles.
          foreach ($public_role_ids as $role) {
            $group_permissions = $this->removeRolePermissionsFromGroup(
              $group_permissions,
              $role,
              $config->get('public_permissions')
            );
          }
          break;

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
        'uri' => $url->toUriString(),
      ],
      'menu_name' => $menu_name,
      'weight' => 2,
    ]);
  }

  /**
   * Returns an Url object for the PRIMARY_OVERVIEW_ROUTE
   * or from canonical with anchor.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  protected function generateFeatureUrl(GroupInterface $group) {
    $url_params = ['group' => $group->id()];
    $options = [
      'fragment' => static::ANCHOR_ID,
    ];

    // If we have an overview route and it's an anchor item, generate
    // a query parameter.
    if (!empty(static::PRIMARY_OVERVIEW_ROUTE) && !empty(static::ANCHOR_ID)) {
      $options['query'][GroupFeatureInterface::QUERY_PARAMETER_OVERVIEW_URL] = Url::fromRoute(
        static::PRIMARY_OVERVIEW_ROUTE,
        $url_params
      )->toString();
    }

    $route_name = !empty(static::ANCHOR_ID) ?
      'entity.group.canonical' :
      static::PRIMARY_OVERVIEW_ROUTE;

    return Url::fromRoute($route_name, $url_params, $options);
  }

  /**
   * Returns the public role IDs of a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity object.
   *
   * @return array
   *   An array of role IDs of the group.
   */
  protected function getGroupPublicRoleIds(GroupInterface $group) {
    return [
      $group->getGroupType()->getAnonymousRoleId(),
      $group->getGroupType()->getOutsiderRoleId(),
    ];
  }

}
