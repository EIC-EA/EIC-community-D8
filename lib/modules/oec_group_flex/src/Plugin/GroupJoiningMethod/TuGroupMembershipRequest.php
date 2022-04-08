<?php

namespace Drupal\oec_group_flex\Plugin\GroupJoiningMethod;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'tu_group_membership_request' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "tu_group_membership_request",
 *  label = @Translation("Request (for trusted users)"),
 *  weight = -90,
 *  visibilityOptions = {
 *   "public",
 *   "flex",
 *   "restricted_community_members",
 *   "custom_restricted"
 *  }
 * )
 */
class TuGroupMembershipRequest extends GroupJoiningMethodBase implements ContainerFactoryPluginInterface {

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
    // Only enable plugin when it doesn't exist yet.
    $contentEnablers = $this->groupContentEnabler->getInstalledIds($groupType);
    if (!in_array('group_membership_request', $contentEnablers)) {
      /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('group_content_type');
      $config = [
        'group_cardinality' => 0,
        'entity_cardinality' => 1,
      ];
      $storage->createFromPlugin($groupType, 'group_membership_request', $config)->save();
    }

    $tuGroupRoleId = $this->getTrustedUserRoleId($groupType);

    $ownerGroupRoleId = EICGroupsHelper::getGroupTypeRole($groupType->id(), 'owner');

    $mappedPerm = [
      $ownerGroupRoleId => [
        'administer membership requests' => TRUE,
      ],
      $tuGroupRoleId => [
        'request group membership' => TRUE,
      ],
    ];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $tuGroupRoleId = $this->getTrustedUserRoleId($groupType);
    $ownerGroupRoleId = EICGroupsHelper::getGroupTypeRole($groupType->id(), 'owner');
    $mappedPerm = [
      $ownerGroupRoleId => [
        'administer membership requests' => FALSE,
      ],
      $tuGroupRoleId => [
        'request group membership' => FALSE,
      ],
    ];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $tuGroupRoleId = $this->getTrustedUserRoleId($group->getGroupType());
    $ownerGroupRoleId = EICGroupsHelper::getGroupTypeRole($group->bundle(), 'owner');
    return [
      $ownerGroupRoleId => ['administer membership requests'],
      $tuGroupRoleId => ['request group membership'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    $tuGroupRoleId = $this->getTrustedUserRoleId($group->getGroupType());
    $ownerGroupRoleId = EICGroupsHelper::getGroupTypeRole($group->bundle(), 'owner');
    return [
      $ownerGroupRoleId => ['administer membership requests'],
      $tuGroupRoleId => ['request group membership'],
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
