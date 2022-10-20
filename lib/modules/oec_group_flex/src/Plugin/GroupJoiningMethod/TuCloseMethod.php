<?php

namespace Drupal\oec_group_flex\Plugin\GroupJoiningMethod;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'tu_close_method' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "tu_close_method",
 *  label = @Translation("Close"),
 *  weight = -80,
 *  visibilityOptions = {
 *   "public",
 *   "flex",
 *   "restricted_community_members",
 *   "custom_restricted"
 *  }
 * )
 */
class TuCloseMethod extends GroupJoiningMethodBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\group\GroupRoleSynchronizer $groupRoleSynchronizer
   *   The group role synchronizer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupRoleSynchronizer $groupRoleSynchronizer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('group_role.synchronizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $ownerGroupRoleId = OECGroupFlexHelper::getGroupTypeRole($group->bundle(), 'owner');
    return [
      $ownerGroupRoleId => ['administer membership requests'],
    ];
  }

  /**
   * Get the trusted user role id for the given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @return string
   *   The group role id.
   */
  private function getTrustedUserRoleId(GroupTypeInterface $groupType) {
    $tuGroupRoleId = $this->groupRoleSynchronizer->getGroupRoleId($groupType->id(), 'trusted_user');
    return $tuGroupRoleId;
  }

}
