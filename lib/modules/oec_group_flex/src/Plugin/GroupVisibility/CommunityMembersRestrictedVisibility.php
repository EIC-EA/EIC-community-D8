<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;

/**
 * Provides a 'restricted_community_members' group visibility.
 *
 * @GroupVisibility(
 *  id = "restricted_community_members",
 *  label = @Translation("Community members only"),
 *  weight = -89
 * )
 */
class CommunityMembersRestrictedVisibility extends RestrictedGroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Community members only');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('This means the restricted group will be visible to each trusted user on the platform.');
  }

}
