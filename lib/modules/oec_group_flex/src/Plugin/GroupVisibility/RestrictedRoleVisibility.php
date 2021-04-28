<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;

/**
 * Provides a 'restricted_role' group visibility.
 *
 * @GroupVisibility(
 *  id = "restricted_role",
 *  label = @Translation("Restricted (visible by members and trusted users)"),
 *  weight = -89
 * )
 */
class RestrictedRoleVisibility extends RestrictedGroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Restricted (The @group_type_name will be viewed by group members and trusted users)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name will be viewed by group members and trusted users', ['@group_type_name' => $groupType->label()]);
  }

}
