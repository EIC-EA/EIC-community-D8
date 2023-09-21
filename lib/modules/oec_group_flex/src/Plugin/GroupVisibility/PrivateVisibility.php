<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group_flex\Plugin\GroupVisibility\PrivateVisibility as PrivateVisibilityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides custom plugin that overrides the 'private' group visibility plugin.
 */
class PrivateVisibility extends PrivateVisibilityBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new PrivateVisibility plugin object.
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
  public function getGroupPermissions(GroupInterface $group): array {
    $groupType = $group->getGroupType();
    $group_view_permissions = ['view group'];

    $installedContentPlugins = $groupType->getInstalledContentPlugins();
    foreach ($installedContentPlugins->getIterator() as $pluginId => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      switch ($plugin->getPluginDefinition()['id']) {
        case 'group_node':
        case 'group_membership':
        case 'group_content_menu':
          $group_view_permissions[] = "view $pluginId entity";
          break;

      }
    }

    // Add perm when anonymous role has permission to view group on group type.
    $memberPermissions = [];
    if ($groupType->getMemberRole()->hasPermission('view group')) {
      $memberPermissions = [$groupType->getMemberRoleId() => $group_view_permissions];
    }

    $outsider_roles = $this->getOutsiderRoles($group->getGroupType());
    foreach ($outsider_roles as $rid) {
      $memberPermissions[$rid] = $group_view_permissions;
    }

    return $memberPermissions;
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
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Private (The @group_type_name can be accessed only by group members, content managers and site admins)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name will be viewed by group members, content managers and site admins', ['@group_type_name' => $groupType->label()]);
  }

}
