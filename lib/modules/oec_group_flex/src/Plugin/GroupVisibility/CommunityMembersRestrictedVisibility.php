<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;

/**
 * Provides a 'restricted_community_members' group visibility.
 *
 * @GroupVisibility(
 *  id = "restricted_community_members",
 *  label = @Translation("Community members only (The group can be accessed only by logged in users)"),
 *  weight = -95
 * )
 */
class CommunityMembersRestrictedVisibility extends RestrictedGroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Community members only (The @group_type_name can be accessed only by logged in users)', ['@group_type_name' => strtolower($groupType->label())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('This means the restricted @group_type_name will be visible to each trusted user on the platform.', ['@group_type_name' => strtolower($groupType->label())]);
  }

}
