<?php

namespace Drupal\oec_group_features;

use Drupal\Core\Config\ConfigFactory;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\GroupPermissionsManagerInterface;

/**
 * GroupFeatureHelper service that provides helper functions for Group features.
 */
class GroupFeatureHelper {

  /**
   * Name of the Features field.
   *
   * @var string
   */
  const FEATURES_FIELD_NAME = 'features';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeaturePluginManager;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a new GroupFeatureHelper.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory service.
   * @param \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_plugin_manager
   *   The Group feature plugin manager.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(
    ConfigFactory $config_factory,
    GroupFeaturePluginManager $group_feature_plugin_manager,
    GroupPermissionsManagerInterface $group_permissions_manager
  ) {
    $this->configFactory = $config_factory;
    $this->groupFeaturePluginManager = $group_feature_plugin_manager;
    $this->groupPermissionsManager = $group_permissions_manager;
  }

  /**
   * Returns all the defined group features.
   *
   * @return array
   *   An array of plugin_id / label.
   */
  public function getAllAvailableFeatures() {
    $available_features = [];
    foreach ($this->groupFeaturePluginManager->getDefinitions() as $definition) {
      $available_features[$definition['id']] = $definition['label'];
    }
    return $available_features;
  }

  /**
   * Returns the available features for a specific group type.
   *
   * @param string $group_type
   *   The group type.
   *
   * @return array
   *   An array of plugin_id / label.
   */
  public function getGroupTypeAvailableFeatures(string $group_type) {
    $available_features = $this->getAllAvailableFeatures();

    if ($config = $this->configFactory->get('oec_group_features.group_type_features.' . $group_type)) {
      $group_type_features = $config->get('features');

      // If the config has specific features enabled, we loop through all
      // features and remove the other ones.
      if (!empty($group_type_features)) {
        foreach ($available_features as $plugin_id => $label) {
          if (!in_array($plugin_id, $group_type_features)) {
            unset($available_features[$plugin_id]);
          }
        }
      }
    }

    return $available_features;
  }

  /**
   * Get the groupPermission object, will create a new one if needed.
   *
   * This is a verbatim copy of
   * Drupal\group_flex\GroupFlexGroupSaver::getGroupPermissionObject().
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to get the group permission object for.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission|null
   *   The (new) group permission object, returns NULL if something went wrong.
   */
  public function getGroupPermissionObject(GroupInterface $group): ?GroupPermission {
    /** @var \Drupal\group_permissions\Entity\GroupPermission $groupPermission */
    $groupPermission = $this->groupPermissionsManager->getGroupPermission($group);

    if ($groupPermission === NULL) {
      // Create the entity.
      $groupPermission = GroupPermission::create([
        'gid' => $group->id(),
        'permissions' => $this->getDefaultGroupTypePermissions($group->getGroupType()),
        'status' => 1,
      ]);
    }
    else {
      $groupPermission->setNewRevision(TRUE);
    }

    return $groupPermission;
  }

  /**
   * Get the default permissions for the given group type.
   *
   * This is a verbatim copy of
   * Drupal\group_flex\GroupFlexGroupSaver::getDefaultGroupTypePermissions().
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type to return the permissions for.
   *
   * @return array
   *   An array of permissions keyed by role.
   */
  public function getDefaultGroupTypePermissions(GroupTypeInterface $groupType): array {
    $permissions = [];

    foreach ($groupType->getRoles() as $groupRoleId => $groupRole) {
      $permissions[$groupRoleId] = $groupRole->getPermissions();
    }

    return $permissions;
  }

}
