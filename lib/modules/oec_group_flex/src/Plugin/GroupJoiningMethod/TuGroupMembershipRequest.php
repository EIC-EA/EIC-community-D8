<?php

namespace Drupal\oec_group_flex\Plugin\GroupJoiningMethod;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;

/**
 * Provides a 'tu_group_membership_request' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "tu_group_membership_request",
 *  label = @Translation("Request (for trusted users)"),
 *  weight = -90,
 *  visibilityOptions = {
 *   "public",
 *   "flex"
 *  }
 * )
 */
class TuGroupMembershipRequest extends GroupJoiningMethodBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    // Only enable plugin when it doesn't exist yet.
    $contentEnablers = $this->groupContentEnabler->getInstalledIds($groupType);
    if (!in_array('group_membership_request', $contentEnablers)) {
      $storage = $this->entityTypeManager->getStorage('group_content_type');
      $config = [
        'group_cardinality' => 0,
        'entity_cardinality' => 1,
      ];
      $storage->createFromPlugin($groupType, 'group_membership_request', $config)->save();
    }

    $tuGroupRoleId = $this->getTrustedUserRoleId($groupType);

    $mappedPerm = [$tuGroupRoleId => ['request group membership' => TRUE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $tuGroupRoleId = $this->getTrustedUserRoleId($groupType);

    $mappedPerm = [$tuGroupRoleId => ['request group membership' => FALSE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $tuGroupRoleId = $this->getTrustedUserRoleId($group->getGroupType());
    return [
      $tuGroupRoleId => ['request group membership'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    $tuGroupRoleId = $this->getTrustedUserRoleId($group->getGroupType());
    return [
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
    /** @var \Drupal\group\GroupRoleSynchronizer $groupRoleSync */
    $groupRoleSync = \Drupal::service('group_role.synchronizer');
    $tuGroupRoleId = $groupRoleSync->getGroupRoleId($groupType->id(), 'trusted_user');
    return $tuGroupRoleId;
  }

}
