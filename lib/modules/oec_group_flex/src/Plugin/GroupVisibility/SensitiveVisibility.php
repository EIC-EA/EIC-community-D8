<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;

/**
 * Provides a 'sensitive_members' group visibility.
 *
 * @GroupVisibility(
 *  id = "sensitive_members",
 *  label = @Translation("Sensitive members only"),
 *  weight = -89
 * )
 */
class SensitiveVisibility extends RestrictedGroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Sensitive members only');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('This means the group will be visible to users allowed to see sensitive content on the platform.');
  }

}
