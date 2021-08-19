<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group_flex\Plugin\GroupVisibilityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends GroupVisibilityBase class for restricted group visibility plugins.
 */
abstract class RestrictedGroupVisibilityBase extends GroupVisibilityBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The OEC module configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $oecGroupFlexConfigSettings;

  /**
   * The group role synchronizer.
   *
   * @var \Drupal\group\GroupRoleSynchronizer
   */
  protected $groupRoleSynchronizer;

  /**
   * Constructs a new RestrictedVisibility plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\group\GroupRoleSynchronizer $groupRoleSynchronizer
   *   The group role synchronizer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, GroupRoleSynchronizer $groupRoleSynchronizer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->oecGroupFlexConfigSettings = $configFactory->get('oec_group_flex.settings');
    $this->groupRoleSynchronizer = $groupRoleSynchronizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('group_role.synchronizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];

    $outsider_roles = $this->getOutsiderRoles($groupType);
    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $mappedPerm[$outsider_role] = ['view group' => TRUE];
      }
    }

    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];

    $outsider_roles = $this->getOutsiderRoles($groupType);
    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $mappedPerm[$outsider_role] = ['view group' => FALSE];
      }
    }

    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $permissions = [
      $group->getGroupType()->getMemberRoleId() => ['view group'],
    ];

    $outsider_roles = $this->getOutsiderRoles($group->getGroupType());
    if (!empty($outsider_roles)) {
      $permissions = $this->setRolesPermission($outsider_roles, 'view group', $permissions);
    }

    $groupType = $group->getGroupType();

    $installedContentPlugins = $groupType->getInstalledContentPlugins();
    foreach ($installedContentPlugins->getIterator() as $pluginId => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      switch ($plugin->getPluginDefinition()['id']) {
        case 'group_node':
        case 'group_membership':
        case 'group_content_menu':
          $permissions[$group->getGroupType()->getMemberRoleId()][] = "view $pluginId entity";

          if (!empty($outsider_roles)) {
            $permissions = $this->setRolesPermission($outsider_roles, "view $pluginId entity", $permissions);
          }
          break;

      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    $permissions = [
      $group->getGroupType()->getAnonymousRoleId() => ['view group'],
      $group->getGroupType()->getOutsiderRoleId() => ['view group'],
    ];

    $outsider_roles = $this->getOutsiderRoles($group->getGroupType());
    if (!empty($outsider_roles)) {
      $permissions = $this->setRolesPermission($outsider_roles, "view group", $permissions);
    }

    $groupType = $group->getGroupType();

    $installedContentPlugins = $groupType->getInstalledContentPlugins();
    foreach ($installedContentPlugins->getIterator() as $pluginId => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      switch ($plugin->getPluginDefinition()['id']) {
        case 'group_node':
        case 'group_membership':
        case 'group_content_menu':
          $permissions[$group->getGroupType()->getAnonymousRoleId()][] = "view $pluginId entity";
          $permissions[$group->getGroupType()->getOutsiderRoleId()][] = "view $pluginId entity";

          if (!empty($outsider_roles)) {
            $permissions = $this->setRolesPermission($outsider_roles, "view $pluginId entity", $permissions);
          }
          break;

      }
    }

    return $permissions;
  }

  /**
   * Get relevant group outsider Drupal roles.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type entity.
   *
   * @return string[]
   *   The outsider roles of the group, keyed by role id.
   */
  protected function getOutsiderRoles(GroupTypeInterface $groupType): array {
    $internal_rids = $this->oecGroupFlexConfigSettings->get('oec_group_visibility_setings.' . $this->pluginId . '.internal_roles');

    $roles = [];
    foreach ($internal_rids as $internal_rid) {
      $role = $this->groupRoleSynchronizer->getGroupRoleId($groupType->id(), $internal_rid);
      $roles[$role] = $role;
    }
    return $roles;
  }

  /**
   * Set permission for multiple roles.
   *
   * @param array $roles
   *   Array containing the list of roles.
   * @param string $permission
   *   The permission to add to the roles.
   * @param array $roles_permissions
   *   Array containing the list of permissions per role.
   *
   * @return array
   *   Array of roles followed by their permissions.
   */
  protected function setRolesPermission(array $roles, $permission, array $roles_permissions = []) {
    if (!empty($roles)) {
      foreach ($roles as $role) {
        $roles_permissions[$role][] = $permission;
      }
    }
    return $roles_permissions;
  }

}
