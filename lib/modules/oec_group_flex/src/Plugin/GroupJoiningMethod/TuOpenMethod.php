<?php

namespace Drupal\oec_group_flex\Plugin\GroupJoiningMethod;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodBase;

/**
 * Provides a 'tu_open_method' group joining method.
 *
 * @GroupJoiningMethod(
 *  id = "tu_open_method",
 *  label = @Translation("Open"),
 *  weight = -100,
 *  visibilityOptions = {
 *   "public",
 *   "flex",
 *   "restricted"
 *  }
 * )
 */
class TuOpenMethod extends GroupJoiningMethodBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType) {
    $tuGroupRoleId = $this->getTrustedUserRoleId($groupType);

    $mappedPerm = [$tuGroupRoleId => ['join group' => TRUE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType) {
    $tuGroupRoleId = $this->getTrustedUserRoleId($groupType);

    $mappedPerm = [$tuGroupRoleId => ['join group' => FALSE]];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $tuGroupRoleId = $this->getTrustedUserRoleId($group->getGroupType());
    return [
      $tuGroupRoleId => ['join group'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    $tuGroupRoleId = $this->getTrustedUserRoleId($group->getGroupType());
    return [
      $tuGroupRoleId => ['join group'],
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
