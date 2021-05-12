<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->oecGroupFlexConfigSettings = $configFactory->get('oec_group_flex.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
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
    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($groupType, $this->oecGroupFlexConfigSettings->get('oec_group_visibility_setings.' . $this->pluginId . '.internal_roles'));

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $mappedPerm[$outsider_role->id()] = ['view group' => TRUE];
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

    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($groupType, $this->oecGroupFlexConfigSettings->get('oec_group_visibility_setings.' . $this->pluginId . '.internal_roles'));

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $mappedPerm[$outsider_role->id()] = ['view group' => FALSE];
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

    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($group->getGroupType(), $this->oecGroupFlexConfigSettings->get('oec_group_visibility_setings.' . $this->pluginId . '.internal_roles'));

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $permissions[$outsider_role->id()] = ['view group'];
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

    $outsider_roles = $this->getOutsiderRolesFromInteralRoles($group->getGroupType(), $this->oecGroupFlexConfigSettings->get('oec_group_visibility_setings.' . $this->pluginId . '.internal_roles'));

    if (!empty($outsider_roles)) {
      foreach ($outsider_roles as $outsider_role) {
        $permissions[$outsider_role->id()] = ['view group'];
      }
    }

    return $permissions;
  }

  /**
   * Get group outsider drupal roles.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type entity.
   * @param array $internal_rids
   *   The outsider role id.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The outsider roles of the group.
   */
  protected function getOutsiderRolesFromInteralRoles(GroupTypeInterface $groupType, array $internal_rids): array {
    $roles = [];
    $group_roles = $groupType->getRoles();
    if (!empty($group_roles)) {
      foreach ($group_roles as $role) {
        foreach ($internal_rids as $key => $internal_rid) {
          if ($role->isInternal() && in_array("user.role.{$internal_rid}", $role->getDependencies()['config'])) {
            $roles[] = $role;
            // We unset the role from $internal_rids array to avoid redundant
            // checks.
            unset($internal_rids[$key]);
            break;
          }
        }
      }
    }
    return $roles;
  }

}
